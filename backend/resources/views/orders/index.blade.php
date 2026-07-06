<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inzra Orders</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f2e8;
            --panel: #fffdf8;
            --ink: #1f2328;
            --muted: #667085;
            --line: #e9dfc9;
            --accent: #ba4a00;
            --accent-soft: #fff1e8;
            --ok: #106b36;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, #fff6d8 0, transparent 26%),
                linear-gradient(180deg, #fdf8ef 0%, var(--bg) 100%);
        }

        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }

        .hero {
            display: grid;
            gap: 14px;
            margin-bottom: 24px;
        }

        .kicker {
            margin: 0;
            font-size: 0.84rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--accent);
        }

        h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.5rem);
            line-height: 1;
        }

        .lede {
            margin: 0;
            max-width: 720px;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.6;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .card {
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
            box-shadow: 0 18px 50px rgba(96, 64, 24, 0.06);
        }

        .card h2,
        .card p {
            margin: 0;
        }

        .metric {
            margin-top: 8px;
            font-size: 2rem;
            font-weight: 700;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
            box-shadow: 0 18px 50px rgba(96, 64, 24, 0.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        th,
        td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }

        th {
            font-size: 0.84rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        tbody tr:hover {
            background: #fff8f0;
        }

        .status {
            display: inline-flex;
            padding: 5px 10px;
            border-radius: 999px;
            background: #edf7ef;
            color: var(--ok);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .subtle {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .pager {
            margin-top: 18px;
        }

        .pager nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pager svg {
            width: 16px;
            height: 16px;
        }

        .pager span,
        .pager a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--panel);
            color: var(--ink);
            text-decoration: none;
            font-size: 0.92rem;
        }

        .pager .active span {
            background: var(--accent-soft);
            border-color: #f6c7ab;
            color: var(--accent);
        }

        @media (max-width: 720px) {
            main {
                padding: 24px 14px 40px;
            }

            .card,
            .table-wrap {
                border-radius: 14px;
            }
        }
    </style>
</head>
<body>
    <main>
        <section class="hero">
            <p class="kicker">Laravel Order Management</p>
            <h1>Inzra Orders</h1>
            <p class="lede">This dashboard lists PayPal orders captured through the Laravel backend. Keep the current admin URL private because access is protected only by the configured order admin key.</p>
        </section>

        <section class="stats">
            <article class="card">
                <h2>Total Orders</h2>
                <p class="metric">{{ number_format($totalOrders) }}</p>
            </article>
            <article class="card">
                <h2>Total Revenue</h2>
                <p class="metric">${{ number_format($totalRevenue, 2) }}</p>
            </article>
            <article class="card">
                <h2>Session</h2>
                <p class="subtle">Authenticated as admin.</p>
                <form method="POST" action="{{ route('admin.logout') }}" style="margin-top:12px">
                    @csrf
                    <button type="submit" style="padding:7px 16px;border-radius:999px;border:1px solid var(--line);background:var(--panel);font-family:inherit;font-size:0.88rem;cursor:pointer;color:var(--accent);font-weight:700;">Sign Out</button>
                </form>
            </article>
        </section>

        <section class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Product</th>
                        <th>Payer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Paid At</th>
                        <th>Mail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <strong>{{ $order->order_id }}</strong>
                                <span class="subtle">Capture: {{ $order->capture_id ?: 'Not stored' }}</span>
                            </td>
                            <td>
                                <strong>{{ $order->title }}</strong>
                                <span class="subtle">Item {{ $order->item_number }}</span>
                            </td>
                            <td>
                                <strong>{{ $order->payer_name ?: 'Customer' }}</strong>
                                <span class="subtle">{{ $order->payer_email ?: 'No email provided' }}</span>
                            </td>
                            <td>
                                <strong>${{ number_format((float) $order->price, 2) }}</strong>
                                <span class="subtle">{{ $order->currency }}</span>
                            </td>
                            <td>
                                <span class="status">{{ $order->capture_status ?: $order->paypal_order_status ?: 'Unknown' }}</span>
                            </td>
                            <td>
                                <strong>{{ optional($order->paid_at)->timezone('UTC')->format('Y-m-d H:i:s') ?: 'Not available' }}</strong>
                                <span class="subtle">UTC</span>
                            </td>
                            <td>
                                <strong>{{ $order->mail_sent_at ? 'Sent' : 'Pending' }}</strong>
                                <span class="subtle">{{ $order->mail_error ?: ($order->mail_sent_at ? $order->mail_sent_at->timezone('UTC')->format('Y-m-d H:i:s').' UTC' : 'No delivery record') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">No orders have been captured by this backend yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div class="pager">
            {{ $orders->links() }}
        </div>
    </main>
</body>
</html>