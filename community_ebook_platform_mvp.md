# Community Business E-Book Platform (MVP Scope)

## Project Overview

This project is a lightweight Community Business E-Book Platform built for:
- Community businesses
- Local organizations
- Universities
- OTOP/community projects
- Small institutions

The goal is NOT to build a large enterprise SaaS platform.

The goal IS to build a clean and modern system where organizations can:
1. Upload E-books
2. Publish them online
3. Let visitors read them in a flipbook-style viewer

The system should prioritize:
- Simplicity
- Clean UI/UX
- Mobile-first responsiveness
- Easy workflow
- Stable performance

---

# Tech Stack

## Backend
- Laravel 13
- Blade
- MySQL

## Frontend
- TailwindCSS
- Vite
- Optional: Alpine.js

## Flipbook / PDF Viewer
Recommended libraries:
- PDF.js
- StPageFlip

DO NOT build a custom PDF engine or flipbook engine from scratch.

---

# System Roles

The platform has 3 roles:
1. Guest/User
2. Company
3. Admin

Company dashboard and Admin dashboard MUST be separate systems.

---

# 1. Guest/User Features

## Purpose
Visitors only need to read E-books.

The user side should remain SIMPLE.

## Required Features

### Ebook Viewer
- Open E-book
- Flip pages
- Next / Previous buttons
- Zoom in/out
- Fullscreen mode
- Responsive mobile layout

### Table of Contents
- Show chapter list
- Allow quick page navigation

### Public Sharing
- Open public E-book link
- Share link

## NOT Required
- User registration
- Comments
- Ratings
- Social system
- Bookmark system
- Payment system

---

# User Flow

Home
→ Open Ebook
→ Read Flipbook

---

# 2. Company Dashboard

## Purpose
Companies upload and manage E-books.

This is the CORE system of the project.

---

# Authentication & Security

## Requirements
- Dashboard must be protected
- Use Laravel auth middleware
- Redirect guests to login
- Session-based authentication

Example:

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

---

# Company Features

## Dashboard Overview

### Basic Statistics
- Total E-books
- Recently uploaded E-books

---

## Ebook Management

### Required CRUD
- Create Ebook
- Edit Ebook
- Delete Ebook

### Upload Features
- Upload PDF
- Upload cover image

### Ebook Metadata
- title
- description
- cover image

---

## Preview
- Preview E-book before publishing

---

## Public Sharing
- Copy public E-book link

---

## Profile Management
- Edit organization name
- Change password
- Upload logo

---

# Suggested Company Sidebar

```txt
Dashboard
Ebooks
Upload Ebook
Profile
Settings
Logout
```

---

# NOT Required for Company

Remove these features:
- Advanced analytics
- Heatmaps
- AI features
- Collaboration tools
- Multiple editors
- Advanced branding system
- Complex categories/tags
- Notifications system

Keep the system lightweight and simple.

---

# Company Flow

Login
→ Dashboard
→ Upload Ebook
→ Publish
→ Share Link

---

# 3. Admin Dashboard

## Purpose
Admins manage the platform.

Admin dashboard MUST be separate from company dashboard.

---

# Admin Features

## Company Management
- View companies
- Delete companies
- Reset company passwords

---

## Ebook Management
- View all E-books
- Delete inappropriate E-books

---

## Basic System Overview
- Total companies
- Total E-books
- Storage usage

---

# Suggested Admin Sidebar

```txt
Dashboard
Companies
Ebooks
System
Logout
```

---

# NOT Required for Admin

Remove:
- Advanced moderation
- Multi-admin system
- Reports system
- Advanced analytics
- Featured ebook system

---

# Database Structure (Simplified)

```sql
companies
- id
- name
- email
- password
- logo

ebooks
- id
- company_id
- title
- description
- pdf_path
- cover_image
- status

admins
- id
- email
- password
```

---

# UI/UX Direction

The UI should feel:
- Clean
- Minimal
- Modern
- Corporate
- Easy to use

Avoid:
- Gaming UI
- Heavy animations
- Neon/glassmorphism
- Overly complex layouts

Use:
- White space
- Strong typography
- Neutral colors
- Responsive layouts

---

# Mobile-First Design

IMPORTANT:
The platform MUST work well on mobile devices.

Priorities:
- Responsive viewer
- Responsive dashboard
- Easy touch interactions
- Readable typography

---

# MVP Priority

## MUST HAVE

### Guest/User
- Read E-book
- Flipbook viewer
- Table of contents

### Company
- Login/Register
- Upload E-book
- Manage E-books
- Share E-books

### Admin
- Manage companies
- Manage E-books

---

# NICE TO HAVE (Optional Later)

- QR code sharing
- Basic analytics
- Dark mode

---

# DO LATER (Out of Scope)

- AI tools
- Payments
- Comments
- Social features
- Collaboration
- Enterprise analytics
- Complex branding systems

---

# Main Goal

The main goal is to create a:
- Simple
- Stable
- Modern
- Responsive

interactive E-book platform for community organizations.

The project should focus on:
- Real usability
- Clean UI
- Good UX
- Simple workflow
- Stable performance

Instead of building too many unnecessary features.
