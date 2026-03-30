# event_system
# 🎓 Student Event Registration System

## 📌 About the Project
The Student Event Registration System is a web-based application developed to simplify the management of college events. It provides a centralized platform where administrators can create and manage events, and students can easily browse and register for them.

This system reduces manual work and improves communication between students and event organizers.

## 👥 User Roles

### 🔑 Admin
- Create and manage events
- Update event details (date, venue, description)
- View registered participants
- Monitor event activities

### 🎓 Student
- Register and login
- View available events
- Register for events
- Access event details and updates

## 🚀 Features

### Admin Side
- Add new events
- Edit and delete events
- View student registrations
- Manage event information

### Student Side
- User authentication (Login/Register)
- Register for events
- View event details


## 🛠️ Technologies Used
- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL
- Server: XAMPP (Apache)

## ⚙️ Prerequisites
Make sure you have the following installed:

- XAMPP / WAMP (Apache & MySQL)
- PHP (7 or above recommended)
- MySQL
- Web Browser (Chrome recommended)
- Git (optional)

---

## ⚙️ Installation & Setup
C:/xampp/htdocs/
Start XAMPP
Start Apache
Start MySQL
Setup Database
Open phpMyAdmin → http://localhost/phpmyadmin
Create a database named:
event_system
Import the provided .sql file
Configure Database Connection

Open your config file (e.g., config/db.php) and update:

$host = "localhost";
$user = "root";
$password = "";
$database = "event_system";
6. Run the Project

Open your browser and go to:

http://localhost/event_system
🎥 Project Demo Video

Watch the full working demo here:
👉 https://your-youtube-link-here

📂 Project Structure
student-event-registration-system/
│── admin/
│── user/
│── config/
│── index.php
│── login.php
│── register.php
│── database.sql
│── README.md

⚠️ Important Notes
Ensure Apache and MySQL are running before accessing the project
If the database connection fails, check credentials in the config file
Do not upload sensitive information (like passwords) to GitHub

👨‍💻 Developed By--Krishna Das

🔮 Future Improvements
Improved UI/UX design
Email notifications for event updates
Mobile responsive design
Advanced search and filtering options
Admin analytics dashboard

### 1. Clone the Repository
```bash
https://github.com/krishnadasss2442-ops/event_system
