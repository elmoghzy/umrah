<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\BranchLogin;
use App\Models\Broker;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BranchLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);

        parent::tearDown();
    }

    public function test_login_page_uses_branch_and_user_name_fields_instead_of_email(): void
    {
        Broker::query()->create(['name' => 'فرع المدينة']);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('الفرع')
            ->assertSee('الفرع الرئيسي')
            ->assertSee('فرع المدينة')
            ->assertSee('اسم المستخدم')
            ->assertSee('كلمة المرور')
            ->assertDontSee('البريد الإلكتروني');
    }

    public function test_user_names_are_preloaded_for_the_selected_branch_and_searchable(): void
    {
        $branchA = Broker::query()->create(['name' => 'فرع مكة']);
        $branchB = Broker::query()->create(['name' => 'فرع المدينة']);
        $mainAdmin = User::factory()->create([
            'name' => 'أحمد المدير',
            'role' => User::ROLE_MAIN_ADMIN,
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'أحمد غير نشط',
            'role' => User::ROLE_MAIN_ADMIN,
            'is_active' => false,
        ]);
        $employeeA = User::factory()->create([
            'name' => 'أحمد موظف مكة',
            'role' => User::ROLE_BRANCH_EMPLOYEE,
            'broker_id' => $branchA->getKey(),
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'أحمد موظف المدينة',
            'role' => User::ROLE_BRANCH_EMPLOYEE,
            'broker_id' => $branchB->getKey(),
            'is_active' => true,
        ]);

        $component = Livewire::test(BranchLogin::class)
            ->set('data.branch_id', 'main')
            ->assertSee('أحمد المدير')
            ->assertDontSee('أحمد موظف مكة')
            ->assertDontSee('أحمد موظف المدينة');

        $component->assertFormFieldExists(
            'user_id',
            fn (Select $field): bool => $field->isSearchable()
                && ! $field->hasDynamicOptions()
                && $field->getOptions() === [$mainAdmin->getKey() => 'أحمد المدير'],
        );

        $component
            ->set('data.branch_id', (string) $branchA->getKey())
            ->assertSee('أحمد موظف مكة')
            ->assertDontSee('أحمد المدير')
            ->assertDontSee('أحمد موظف المدينة')
            ->assertFormFieldExists(
                'user_id',
                fn (Select $field): bool => $field->isSearchable()
                    && ! $field->hasDynamicOptions()
                    && $field->getOptions() === [$employeeA->getKey() => 'أحمد موظف مكة'],
            );
    }

    public function test_main_admin_can_sign_in_from_the_main_branch(): void
    {
        $admin = User::factory()->create([
            'name' => 'مدير النظام',
            'email' => 'admin.login@example.test',
            'role' => User::ROLE_MAIN_ADMIN,
            'is_active' => true,
            'password' => 'branch-password',
        ]);

        Livewire::test(BranchLogin::class)
            ->fillForm([
                'branch_id' => 'main',
                'user_id' => $admin->getKey(),
                'password' => 'branch-password',
            ])
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_employee_can_sign_in_only_from_their_assigned_branch(): void
    {
        $branchA = Broker::query()->create(['name' => 'فرع مكة']);
        $branchB = Broker::query()->create(['name' => 'فرع المدينة']);
        $employee = User::factory()->create([
            'name' => 'موظف مكة',
            'role' => User::ROLE_BRANCH_EMPLOYEE,
            'broker_id' => $branchA->getKey(),
            'is_active' => true,
            'password' => 'branch-password',
        ]);

        Livewire::test(BranchLogin::class)
            ->fillForm([
                'branch_id' => (string) $branchA->getKey(),
                'user_id' => $employee->getKey(),
                'password' => 'branch-password',
            ])
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($employee);

        auth()->logout();

        Livewire::test(BranchLogin::class)
            ->fillForm([
                'branch_id' => (string) $branchB->getKey(),
                'user_id' => $employee->getKey(),
                'password' => 'branch-password',
            ])
            ->call('authenticate')
            ->assertHasErrors();

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_sign_in(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_MAIN_ADMIN,
            'is_active' => false,
            'password' => 'branch-password',
        ]);

        Livewire::test(BranchLogin::class)
            ->fillForm([
                'branch_id' => 'main',
                'user_id' => $admin->getKey(),
                'password' => 'branch-password',
            ])
            ->call('authenticate')
            ->assertHasErrors();

        $this->assertGuest();
    }
}
