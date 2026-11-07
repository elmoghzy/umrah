<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'مواكب') }}</title>
        <style>
            :root {
                --bg: #f6f1e7;
                --card: rgba(255, 255, 255, 0.9);
                --border: #e6d8b1;
                --primary: #104f6b;
                --secondary: #d7a11a;
                --text: #1e293b;
                --muted: #4b5563;
            }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                background: linear-gradient(135deg, #f8f3ea 0%, #eaf6fb 100%);
                font-family: "Tahoma", "Segoe UI", sans-serif;
                color: var(--text);
            }

            .card {
                width: min(680px, calc(100% - 32px));
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 24px;
                box-shadow: 0 24px 60px rgba(16, 79, 107, 0.12);
                padding: 40px 32px;
                text-align: center;
            }

            .badge {
                display: inline-block;
                padding: 8px 14px;
                border-radius: 999px;
                background: rgba(16, 79, 107, 0.08);
                color: var(--primary);
                font-weight: 700;
                letter-spacing: 0.04em;
            }

            h1 {
                margin: 22px 0 18px;
                color: var(--primary);
                font-size: clamp(2rem, 4vw, 3rem);
                line-height: 1.35;
            }

            p {
                margin: 0;
                color: var(--muted);
                font-size: 1.1rem;
                line-height: 1.8;
            }

            .list {
                list-style: none;
                padding: 0;
                margin: 28px 0 0;
                display: grid;
                gap: 12px;
                text-align: right;
            }

            .list li {
                display: flex;
                align-items: center;
                gap: 12px;
                background: rgba(215, 161, 26, 0.08);
                border: 1px solid rgba(215, 161, 26, 0.18);
                border-radius: 14px;
                padding: 12px 16px;
                font-weight: 600;
            }

            .list li::before {
                content: "✓";
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background: rgba(39, 149, 95, 0.1);
                color: #1f8a5b;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <main class="card">
            <div class="badge">مواكب</div>
            <h1>مرحباً بكم في منظومة حجوزات العمرة</h1>
            <p>نظام متكامل لإدارة الحجوزات، المعتمرين، التأشيرات، المواصلات، والتقارير من واجهة واحدة.</p>

            <ul class="list">
                <li>إدارة الحجوزات والفرعيات</li>
                <li>متابعة حالات التأشيرات والمعتمرين</li>
                <li>تقارير سريعة ومطبوعات احترافية</li>
            </ul>
        </main>
    </body>
</html>
