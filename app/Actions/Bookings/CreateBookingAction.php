<?php

namespace App\Actions\Bookings;

use App\Enums\StayScope;
use App\Models\Booking;
use App\Models\City;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\Transport;
use App\Models\Visa;
use App\Enums\VisaStatus;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateBookingAction
{
    public function handle(array $data): Booking
    {
        if (auth()->user()) {
            Gate::authorize('create', Booking::class);
        }

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return DB::transaction(function () use ($data): Booking {
                    $normalized = $this->validateAndNormalize($data);
                    $booking = new Booking($normalized['booking']);
                    $booking->booking_number = ((int) Booking::query()->lockForUpdate()->max('booking_number')) + 1;
                    $booking->created_by = auth()->id();
                    $booking->updated_by = auth()->id();
                    $booking->save();

                    $this->syncRelations($booking, $normalized);

                    return $booking->fresh(['details.rooms', 'residents', 'transfers']);
                });
            } catch (QueryException $exception) {
                if ($attempt === 3 || ! $this->isUniqueViolation($exception)) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('تعذر إنشاء الحجز.');
    }

    /**
     * @return array{booking: array<string, mixed>, details: array<int, array<string, mixed>>, residents: array<int, int>, rooms: array<int, array<string, mixed>>, transfers: array<int, array<string, mixed>>}
     */
    public function validateAndNormalize(array $data): array
    {
        $user = auth()->user();
        if ($user && ! $user->isMainAdmin() && (! $user->isBranchEmployee() || ! $user->broker_id)) {
            throw ValidationException::withMessages(['broker_id' => 'يجب ربط المستخدم بفرع قبل إنشاء الحجز.']);
        }

        $brokerId = $user && ! $user->isMainAdmin()
            ? $user->broker_id
            : ($data['broker_id'] ?? null);

        $transfers = $this->normalizeTransfers($data['transfers'] ?? []);
        $transportAmount = round(collect($transfers)->sum('price'), 2);

        $rooms = array_values(array_filter($data['rooms'] ?? [], fn (mixed $room): bool => filled($room['room_type_id'] ?? null)));
        if ($rooms === []) {
            throw ValidationException::withMessages(['rooms' => 'يجب إضافة غرفة واحدة على الأقل.']);
        }

        $scopeState = $data['stay_scope'] ?? StayScope::Both;
        $scope = $scopeState instanceof StayScope ? $scopeState->value : (int) $scopeState;
        $details = [];
        foreach ($this->citiesForScope($scope) as $city => $prefix) {
            $detail = [
                'city_id' => $city,
                'hotel_id' => $data[$prefix.'_hotel_id'] ?? null,
                'company_id' => $data[$prefix.'_company_id'] ?? null,
                'meal_id' => $data[$prefix.'_meal_id'] ?? null,
                'check_in' => $data[$prefix.'_check_in'] ?? null,
                'check_out' => $data[$prefix.'_check_out'] ?? null,
            ];

            if (! $detail['hotel_id'] || ! $detail['company_id'] || ! $detail['meal_id'] || ! $detail['check_in'] || ! $detail['check_out']) {
                throw ValidationException::withMessages([$prefix.'_hotel_id' => 'بيانات الفندق والجهات والتواريخ مطلوبة.']);
            }

            if (! Hotel::query()->whereKey($detail['hotel_id'])->where('city_id', $city)->exists()) {
                throw ValidationException::withMessages([$prefix.'_hotel_id' => 'الفندق المحدد لا يتبع المدينة المختارة.']);
            }

            if ($detail['check_out'] < $detail['check_in']) {
                throw ValidationException::withMessages([$prefix.'_check_out' => 'تاريخ الخروج يجب أن يكون بعد الدخول.']);
            }

            $details[] = $detail;
        }

        $customerId = (int) ($data['customer_id'] ?? 0);
        $customer = Customer::query()->with('dependents')->find($customerId);
        if (! $customer) {
            throw ValidationException::withMessages(['customer_id' => 'ولي الأمر مطلوب.']);
        }

        $residents = array_values(array_unique([
            $customerId,
            ...$customer->dependents->modelKeys(),
        ]));

        $total = 0.0;
        foreach ($rooms as &$room) {
            $roomType = RoomType::query()->find($room['room_type_id']);
            if (! $roomType) {
                throw ValidationException::withMessages(['rooms' => 'نوع الغرفة المحدد غير موجود.']);
            }

            $room['price'] = round((float) ($room['price'] ?? $roomType->price), 2);
            $room['total_price'] = round($room['price'] * (int) $roomType->size, 2);
            $total += $room['total_price'];
        }
        unset($room);

        $serviceAmount = round((float) ($data['service_amount'] ?? 0), 2);
        $discount = round((float) ($data['discount'] ?? 0), 2);
        if ($discount > ($total + $serviceAmount + $transportAmount)) {
            throw ValidationException::withMessages(['discount' => 'التخفيض لا يمكن أن يتجاوز إجمالي الغرف والخدمات والمواصلات.']);
        }

        return [
            'booking' => [
                'customer_id' => $customerId,
                'broker_id' => $brokerId,
                'airplane_id' => $data['airplane_id'] ?? null,
                'booking_type_id' => $data['booking_type_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'services_note' => $data['services_note'] ?? null,
                'service_amount' => $serviceAmount,
                'total_amount' => $total,
                'transport_amount' => $transportAmount,
                'discount' => $discount,
                'net_amount' => round($total + $serviceAmount + $transportAmount - $discount, 2),
                'stay_scope' => $scope,
            ],
            'details' => $details,
            'residents' => $residents,
            'rooms' => $rooms,
            'transfers' => $transfers,
        ];
    }

    /** @param array{booking: array<string, mixed>, details: array<int, array<string, mixed>>, residents: array<int, int>, rooms: array<int, array<string, mixed>>, transfers: array<int, array<string, mixed>>} $normalized */
    public function syncRelations(Booking $booking, array $normalized): void
    {
        $booking->residents()->createMany(array_map(fn (int $customerId): array => ['customer_id' => $customerId], $normalized['residents']));
        $this->syncPendingVisas($booking, $normalized['residents']);

        foreach ($normalized['details'] as $detailData) {
            $detail = $booking->details()->create($detailData);
            $detail->rooms()->createMany(array_map(
                fn (array $room): array => [
                    'room_type_id' => $room['room_type_id'],
                    'price' => $room['price'],
                    'total_price' => $room['total_price'],
                ],
                $normalized['rooms'],
            ));
        }

        $booking->transfers()->createMany($normalized['transfers']);
    }

    /** @param array<int, int> $customerIds */
    private function syncPendingVisas(Booking $booking, array $customerIds): void
    {
        foreach ($customerIds as $customerId) {
            $visa = Visa::withTrashed()
                ->where('booking_id', $booking->getKey())
                ->where('customer_id', $customerId)
                ->first();

            if ($visa) {
                if ($visa->trashed() && $visa->status === VisaStatus::Pending) {
                    $visa->restore();
                }

                continue;
            }

            $booking->visas()->create([
                'customer_id' => $customerId,
                'status' => VisaStatus::Pending,
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    protected function normalizeTransfers(mixed $transfers): array
    {
        $validated = Validator::make(['transfers' => $transfers], [
            'transfers' => ['array'],
            'transfers.*' => ['array'],
            'transfers.*.transport_id' => ['required', 'integer'],
            'transfers.*.from_city_id' => ['required', 'integer'],
            'transfers.*.to_city_id' => ['required', 'integer'],
            'transfers.*.travel_date' => ['nullable', 'date'],
            'transfers.*.travel_time' => ['nullable', 'date_format:H:i'],
            'transfers.*.price' => ['nullable', 'numeric', 'min:0'],
            'transfers.*.note' => ['nullable', 'string', 'max:255'],
        ])->validate()['transfers'] ?? [];

        $validated = array_values($validated);
        if ($validated === []) {
            return [];
        }

        $transportIds = collect($validated)
            ->pluck('transport_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->all();
        $cityIds = collect($validated)
            ->flatMap(fn (array $transfer): array => [(int) $transfer['from_city_id'], (int) $transfer['to_city_id']])
            ->unique()
            ->all();
        $existingTransportIds = Transport::query()->whereKey($transportIds)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $existingCityIds = City::query()->whereKey($cityIds)->pluck('id')->map(fn ($id): int => (int) $id)->all();

        foreach ($validated as $index => $transfer) {
            $transportId = (int) $transfer['transport_id'];
            $fromCityId = (int) $transfer['from_city_id'];
            $toCityId = (int) $transfer['to_city_id'];

            if (! in_array($transportId, $existingTransportIds, true)) {
                throw ValidationException::withMessages([
                    "transfers.{$index}.transport_id" => 'وسيلة المواصلات المحددة غير موجودة أو محذوفة.',
                ]);
            }

            if (! in_array($fromCityId, $existingCityIds, true)) {
                throw ValidationException::withMessages([
                    "transfers.{$index}.from_city_id" => 'مدينة المغادرة المحددة غير موجودة.',
                ]);
            }

            if (! in_array($toCityId, $existingCityIds, true)) {
                throw ValidationException::withMessages([
                    "transfers.{$index}.to_city_id" => 'مدينة الوصول المحددة غير موجودة.',
                ]);
            }

            if ($fromCityId === $toCityId) {
                throw ValidationException::withMessages([
                    "transfers.{$index}.to_city_id" => 'مدينة الوصول يجب أن تختلف عن مدينة المغادرة.',
                ]);
            }

            $validated[$index] = [
                'transport_id' => $transportId,
                'from_city_id' => $fromCityId,
                'to_city_id' => $toCityId,
                'travel_date' => $transfer['travel_date'] ?? null,
                'travel_time' => $transfer['travel_time'] ?? null,
                'price' => round((float) ($transfer['price'] ?? 0), 2),
                'note' => $transfer['note'] ?? null,
            ];
        }

        return $validated;
    }

    /** @return array<int, string> */
    protected function citiesForScope(int $scope): array
    {
        return match ($scope) {
            1 => [2 => 'madina'],
            2 => [1 => 'makka'],
            default => [2 => 'madina', 1 => 'makka'],
        };
    }

    protected function isUniqueViolation(QueryException $exception): bool
    {
        return $exception->getCode() === '23000' || str_contains(strtolower($exception->getMessage()), 'unique');
    }
}
