# ChaseFlow — Automated Invoice Payment Follow-up

ChaseFlow is a production-style Laravel application that automates the painful part of
freelancing and agency work: **chasing unpaid invoices**.

An invoice passes its due date → a scheduled command dispatches queued jobs → polite
escalation emails go out automatically (Day 1, Day 7, Day 14 with an automatic late fee),
every send is recorded in an audit log with its AWS SES message ID, and a dashboard shows
cash-flow forecasts.

Built with **Laravel 11 (PHP 8.3) · MySQL · Redis queues · AWS SES + S3 · GitHub Actions · Render**.

## The automation ladder

| Time after due date | Action |
|---|---|
| Day 0 | Invoice PDF generated, stored on S3, emailed to client |
| Day +1 | Friendly reminder (queued email) |
| Day +7 | Firmer reminder (queued email) |
| Day +14 | Final notice — late fee auto-applied per your % setting |

Every email is a queued job with retries, and every send is written to `reminder_logs`
with the SES message ID — an audit trail you can show any client.

## Local setup

```bash
composer create-project laravel/laravel chaseflow
cd chaseflow
# copy the contents of this repo over the fresh skeleton, then:
composer install
cp .env.example .env
php artisan key:generate
# create a MySQL database named "chaseflow", then:
php artisan migrate --seed   # creates admin@chaseflow.test / password
npm i && npm run build       # or: use the Tailwind CDN layout as-is for dev
php artisan serve
```

Login: **admin@chaseflow.test / password**

> Email locally: set `MAIL_MAILER=log` in `.env` to inspect emails in `storage/logs/laravel.log`.
> AWS: set `MAIL_MAILER=ses` with SES credentials for production (see below).

## Running the automation locally

```bash
php artisan queue:work            # in one terminal
php artisan schedule:run          # nightly in another (or set up cron)
# or trigger manually:
php artisan chaseflow:process-overdue-invoices
```

Create an overdue invoice (due date in the past), run the command, and watch the
reminder ladder fire — visible in `storage/logs` and the invoice detail page timeline.

## Tests

```bash
php artisan test
```

Covers the critical paths: reminder ladder deduplication, late fee calculation and
idempotency, and paid-invoice exclusion.

## Deployment on Render (Blueprint)

1. Push this repo to GitHub.
2. In Render: **New → Blueprint** → connect the repo → it reads `render.yaml`.
3. Add env vars in the Render dashboard when prompted (marked `sync: false`):
   - `APP_URL` (your Render URL)
   - `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`
   - `MAIL_FROM_ADDRESS` (must be a verified SES sender/domain)
4. Create the S3 bucket + SES identity in AWS (free tier covers both for this scale).
5. Render builds the web service, a queue worker, a cron runner (nightly schedule),
   managed MySQL and Redis automatically.

`render.yaml` provisions: web service (Docker), worker (`queue:work`), cron
(`schedule:run` nightly), MySQL, Redis.

## AWS notes

- **SES**: start in the SES sandbox; verify your sender email/domain, then request
  production access. ChaseFlow uses Laravel's `ses` mailer — emails go through the
  AWS SDK, and the returned SES message ID is captured into `reminder_logs`.
- **S3**: one private bucket for generated invoice PDFs.
- Free tier: 62,000 SES emails/month and 5 GB S3 — effectively free for a portfolio/demo.

## Git workflow

- `main` is protected: PRs must pass GitHub Actions (Pint + Pest) before merge.
- Merging to `main` auto-deploys to Render (set the `RENDER_DEPLOY_HOOK_URL` secret
  and uncomment the deploy step in `.github/workflows/ci.yml`, or connect the repo
  in Render's dashboard for auto-deploy).

## Project structure

```
app/
├── Console/Commands/ProcessOverdueInvoices.php   # nightly automation entry point
├── Http/Controllers/{Auth,Client,Invoice,Dashboard}Controller.php
├── Jobs/SendInvoiceReminder.php                  # queued escalation email
├── Mail/{InvoiceReminderMail,InvoiceSentMail}.php
├── Models/{User,Client,Invoice,ReminderLog,Setting}.php
├── Services/{ReminderService,LateFeeService,CashFlowForecastService}.php
└── Support/MessageId.php                         # captures SES message ID
database/{migrations,factories,seeders}
resources/views/{layouts,auth,clients,invoices,emails,dashboard}
tests/Feature/{ReminderLadderTest,LateFeeTest}.php
```

## License
MIT — use it as a portfolio piece, an Upwork demo, or a real product.
