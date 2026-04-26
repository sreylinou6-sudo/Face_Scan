# FaceScan — PHP Face Detection System

## Requirements
- PHP 7.4+ (with GD extension for thumbnails)
- A web server: Apache, Nginx, or PHP's built-in server
- Modern browser with camera (Chrome, Firefox, Edge)
- HTTPS or localhost (required for camera access)

## Quick Start

### Option 1: PHP Built-in Server (easiest)
```bash
cd facescan/
php -S localhost:8080
```
Then open: http://localhost:8080

### Option 2: XAMPP / WAMP / MAMP
Copy the `facescan/` folder into your `htdocs` or `www` directory, then visit:
http://localhost/facescan/

### Option 3: Apache/Nginx
Drop the folder in your web root and configure the vhost normally.

## Files
- `index.php`   — Main scanner interface
- `save.php`    — Handles capture saves (POST endpoint)
- `gallery.php` — View all captured faces
- `clear.php`   — Clear session log
- `uploads/`    — Saved face images
- `logs/`       — Text scan log

## Features
- Real-time face detection with bounding boxes + landmarks
- Age & gender estimation
- Expression analysis (7 emotions)
- PHP session-based capture log
- Image saved to server with PHP
- Scan log written to `logs/scans.log`
