## Axis SMS - Multi-tenant School Management System

Axis SMS is a multi-tenant school management system built with **PHP**, **MySQL**, **HTML**, **CSS**, and **JavaScript**, using **Firebase Authentication** for user login.

This project is structured for use with XAMPP on Windows. The MySQL database name is **axis_sms**.

### Tech Stack
- **Backend**: PHP (no framework)
- **Database**: MySQL (`axis_sms`)
- **Frontend**: HTML, CSS, JavaScript
- **Auth**: Google Firebase Authentication (Web SDK + server-side verification)

### Tenants & Roles
- Each school is a **tenant**.
- Users are linked to a school via Firebase `uid`.
- Supported roles:
  - Admin
  - Accountant
  - Bus Driver
  - Teacher
  - Parent

### Environment Variables


### Basic Setup

1. Create the MySQL database:
   - Name: `axis_sms`
   - Charset: `utf8mb4`
2. Import the schema:
   - Use the SQL file in `database/schema.sql` (to be kept up to date).
3. Create a `.env` file in the project root based on the example above.
4. Configure Firebase in the web client:
   - Edit `assets/js/firebase-config.js` with your Firebase config.

### Admin Dashboard (Initial Focus)

The first milestone focuses on the **Admin** dashboard:
- Manage Students (Add, View, Edit, Delete)
- Manage Teachers (Add, View, Edit, Delete)
- Manage Classes (Add, View, Edit, Delete)
- Manage Subjects (Add, View, Edit, Delete)
- Manage Parents (Add, View, Edit, Delete)
- Add Accountant
- Add Bus Driver, configure routes, assign students to routes
- View Attendance, Grades, Teachers’ Lesson Plans
- Timetable management
- Dashboard, Reports, Analytics, Settings (school name, logo, accent color, exam result branding)

This repository contains a clean PHP structure to extend each of these areas incrementally.

