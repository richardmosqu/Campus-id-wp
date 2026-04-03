# Campus ID — WordPress Plugin

A WordPress plugin that lets university students create and display a unique digital student ID card linked to their WordPress account.

## Features

- Students fill out a simple form to generate their ID
- Each ID gets a unique serial number (CL-XXXXXX)
- QR code generated automatically for ID validation
- Admin dashboard to view, edit, and delete student IDs
- Shortcode-based — display the ID on any WordPress page
- Photo upload support

## Shortcodes

| Shortcode | Description |
|---|---|
| `[campuslife_create_id]` | Form to create a new ID |
| `[campuslife_show_id]` | Displays the student's ID card |
| `[campuslife_edit_id]` | Form to edit an existing ID |
| `[campuslife_validate_id]` | Validate an ID by serial number |

## Built With

- PHP
- WordPress Plugin API
- QRCode.js

## Author

Richard Mosqueda — [campuslifepa.com](https://campuslifepa.com)