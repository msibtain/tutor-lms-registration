# Tutor LMS Registration

WordPress plugin that provides shortcodes for **teacher (instructor)** and **student** registration. Teacher registrants get the **Subscriber** role and are set up as **Tutor LMS Instructors** (pending approval). Student registrants get the **Subscriber** role only.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- [Tutor LMS](https://wordpress.org/plugins/tutor/) plugin (must be installed and active)

## Installation

1. Copy the `tutor-lms-registration` folder to `wp-content/plugins/`
2. Activate the plugin in **Plugins** → **Installed Plugins**
3. Ensure Tutor LMS is installed and activated

## Usage

### Teacher (instructor) registration

Add the shortcode to any page or post:

```
[tutor_registration]
```

### Student registration

Same form fields as teacher registration. Use this shortcode for student sign-up:

```
[tutor_student_registration]
```

Or use either in a template:

```php
<?php echo do_shortcode( '[tutor_registration]' ); ?>
<?php echo do_shortcode( '[tutor_student_registration]' ); ?>
```

## Registration Form Fields

- **First Name** (required)
- **Last Name** (required)
- **Username** (required)
- **Email** (required)
- **Password** (required, minimum 6 characters)

## What Happens on Registration

### Teacher registration (`[tutor_registration]`)

1. A new WordPress user is created with the **Subscriber** role
2. The user is marked as a **pending Tutor LMS Instructor** (`_tutor_instructor_status` = `pending`)
3. After admin approval, the user can access the Tutor LMS instructor dashboard and create courses

### Student registration (`[tutor_student_registration]`)

1. A new WordPress user is created with the **Subscriber** role
2. No instructor meta is set; the user is a student only and can enroll in courses

## File Structure

```
tutor-lms-registration/
├── tutor-lms-registration.php   # Main plugin file
├── assets/
│   └── css/
│       └── registration-form.css
└── README.md
```

## Hooks

- `tlr_after_instructor_registration` – Fires after a user is successfully registered as an instructor. Passes the new user ID.
- `tlr_after_student_registration` – Fires after a user is successfully registered as a student. Passes the new user ID.
