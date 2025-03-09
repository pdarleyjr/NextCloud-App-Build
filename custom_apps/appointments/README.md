# Therapist Appointments App for Nextcloud

This Nextcloud app provides a comprehensive solution for therapists to manage appointments, scheduling, and billing.

## Features

- **Multiple Therapists**: Each Nextcloud user can be a therapist with their own schedule and client list
- **Advanced Scheduling**: Support for recurring appointments, cancellations, and rescheduling
- **Invoice & Superbill Generation**: Create professional invoices for therapy sessions
- **Square Payment Integration**: Accept payments directly through the app using Square

## Installation

1. Place this app in the `custom_apps` directory of your Nextcloud installation
2. Enable the app from the Nextcloud admin panel
3. Configure Square API credentials in the app settings

## Configuration

### Square API Setup

1. Create a Square Developer account at [https://developer.squareup.com/](https://developer.squareup.com/)
2. Create a new application in the Square Developer Dashboard
3. Get your Application ID and Access Token
4. In the Nextcloud admin panel, go to the Appointments app settings and enter your Square credentials

### Setting Up Therapists

1. Go to the Appointments app
2. Click on "Become a Therapist" to set up your therapist profile
3. Configure your schedule, hourly rate, and specialties

## Usage

### For Therapists

1. Set your available hours in the Schedule tab
2. View and manage appointments in the Appointments tab
3. Create invoices for completed sessions
4. Track payments and generate reports

### For Clients

1. Browse available therapists
2. Book appointments based on therapist availability
3. View upcoming and past appointments
4. Pay invoices directly through the app

## Development

### Building the App

```bash
# Install dependencies
composer install
npm install

# Build frontend assets
npm run build
```

### Running Tests

```bash
composer test
```

## License

This app is licensed under the AGPL-3.0-or-later license.