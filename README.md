# 🎫 PHP Mail-to-Ticket System

A modern, full-featured ticketing system written in PHP that automatically converts incoming emails into support tickets with attachment storage, automatic username resolution, ticket reply tracking, and a glassmorphic dashboard interface.

---

## 🌟 Key Features

1. **Automatic Email-to-Ticket Conversion**:
   - **Mail `From` Address** &rarr; Automatically registered as the submitter's email and username (e.g. `john.doe@company.com` &rarr; `john.doe`).
   - **Mail `Subject`** &rarr; Ticket subject.
   - **Mail `Description / Body`** &rarr; Ticket description (supports HTML & plain text formatting).
   - **Mail `Attachments`** &rarr; Downloaded, stored safely in `public/uploads/attachments/`, and linked directly to the ticket.

2. **Multiple Mail Ingestion Methods**:
   - **IMAP / POP3 Poller Script** (`cron/fetch_emails.php`): Fetches unread messages from your IMAP mailbox (Gmail, Outlook, custom mail servers).
   - **REST Webhook API** (`public/api/incoming_mail.php`): Ingest inbound emails from services like SendGrid, Mailgun, Postmark, or custom email forwarders via HTTP POST.
   - **Interactive Web Simulator** (`public/simulate.php`): Test and demonstrate ticket creation with custom senders, subjects, bodies, and attachments without requiring live SMTP credentials.

3. **Modern Web UI Dashboard**:
   - Built with modern glassmorphism aesthetics, responsive dark layout, micro-animations, and clean typography.
   - Real-time search and status filtering (`Open`, `In Progress`, `Resolved`, `Closed`).
   - Priority badges (`Low`, `Medium`, `High`, `Urgent`).
   - Ticket detail view with reply conversation thread and attachment download cards.

4. **Zero-Config Database**:
   - Driven by PDO SQLite (`database/database.sqlite`) out of the box with automatic table creation, plus seamless MySQL/MariaDB fallback support in `config/database.php`.

---

## 📁 Directory Structure

```
mail-ticket/
├── config/
│   ├── app.php
│   ├── database.php        # SQLite/MySQL settings
│   └── mail.php            # IMAP/POP3 & attachment settings
├── database/
│   ├── schema.sql          # DB Schema
│   └── database.sqlite     # SQLite Database (Auto-created)
├── public/
│   ├── api/
│   │   └── incoming_mail.php # Webhook endpoint
│   ├── css/
│   │   └── style.css       # Glassmorphism CSS styles
│   ├── js/
│   │   └── app.js          # Interactive UI JavaScript
│   ├── uploads/
│   │   └── attachments/   # Saved ticket attachments
│   ├── download.php        # Secure attachment downloader
│   ├── index.php           # Main Dashboard
│   ├── settings.php        # Integration settings
│   ├── simulate.php        # Email simulator
│   └── ticket_view.php     # Ticket details & replies
├── src/
│   ├── Database.php        # PDO Connection & Migration
│   ├── Models/
│   │   ├── Attachment.php
│   │   ├── Ticket.php
│   │   └── User.php
│   └── Services/
│       ├── EmailFetcher.php
│       ├── EmailParser.php
│       └── TicketService.php
├── cron/
│   └── fetch_emails.php    # CLI Cron Fetcher
├── composer.json
└── README.md
```

---

## 🚀 Quick Start Guide

### 1. Requirements
- PHP >= 8.0 with `pdo_sqlite` or `pdo_mysql` enabled.
- Composer.

### 2. Run Locally
Run the built-in PHP web server from the project directory:

```bash
php -S localhost:8000 -t public
```

Open your browser and navigate to:
[http://localhost:8000](http://localhost:8000)

---

## 🧪 Testing Email Ticket Creation

1. Go to [http://localhost:8000/simulate.php](http://localhost:8000/simulate.php).
2. Enter a sender email (e.g., `jane.smith@acme.com`), subject, message body, and attach sample files (PDF, PNG, Log files, etc.).
3. Click **Simulate Mail Arrival & Create Ticket**.
4. You will be redirected to the newly created ticket view where you can view the description, download attachments, update status, and post replies!

---

## ⚙️ Setting Up Automatic Email Fetching (IMAP)

1. Open `config/mail.php` and update your mail credentials:
   ```php
   'incoming' => [
       'enabled'    => true,
       'protocol'   => 'imap',
       'host'       => 'imap.gmail.com',
       'port'       => 993,
       'encryption' => 'ssl',
       'username'   => 'your-support-email@gmail.com',
       'password'   => 'your-app-password',
   ]
   ```

2. Test fetching manually:
   ```bash
   php cron/fetch_emails.php
   ```

3. Set up a Cron job (Linux) or Scheduled Task (Windows):
   ```cron
   */5 * * * * php /path/to/mail-ticket/cron/fetch_emails.php
   ```

---

## 📄 License
MIT License. Created for Maulik Dawda.
