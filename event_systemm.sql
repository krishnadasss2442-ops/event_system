CREATE DATABASE IF NOT EXISTS event_system;
USE event_system;

-- ============================================================
--  ADBU Azara – Student Event Registration System
--  Full Database Setup Script
--  Run this file once in phpMyAdmin or MySQL CLI
-- ============================================================

-- 1. Create & select the database
CREATE DATABASE IF NOT EXISTS adbu_events
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE adbu_events;

-- ============================================================
-- TABLE: users
-- Stores both students (role='student') and admins (role='admin')
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id         INT          NOT NULL AUTO_INCREMENT,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(180) NOT NULL,
  password   VARCHAR(255) NOT NULL,          -- bcrypt hash
  role       ENUM('student','admin')
             NOT NULL DEFAULT 'student',
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: events
-- Created and managed by admin users
-- ============================================================
CREATE TABLE IF NOT EXISTS events (
  id               INT           NOT NULL AUTO_INCREMENT,
  title            VARCHAR(220)  NOT NULL,
  description      TEXT,
  event_date       DATETIME      NOT NULL,
  venue            VARCHAR(220)  NOT NULL,
  total_seats      INT           NOT NULL DEFAULT 0,
  available_seats  INT           NOT NULL DEFAULT 0,
  created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: registrations
-- Tracks which student registered for which event
-- ============================================================
CREATE TABLE IF NOT EXISTS registrations (
  id          INT       NOT NULL AUTO_INCREMENT,
  user_id     INT       NOT NULL,
  event_id    INT       NOT NULL,
  registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_reg (user_id, event_id),          -- one entry per student per event
  CONSTRAINT fk_reg_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED: Admin account
-- Email   : admin@adbu.in
-- Password: admin123   <-- CHANGE THIS after first login!
-- ============================================================
INSERT INTO users (name, email, password, role) VALUES (
  'Site Administrator',
  'admin@adbu.in',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt of "admin123"
  'admin'
);

-- ============================================================
-- SEED: Sample student accounts
-- Password for all sample students: student123
-- ============================================================
INSERT INTO users (name, email, password, role) VALUES
  ('Krishna Das',    'krishna@adbu.in',  '$2y$10$TKh8H1.PfuBifdecVpQ2vOuLz4EsZc4l3lhInSVQ9E.ZaVoZVXBZi', 'student'),
  ('Priya Sharma',   'priya@adbu.in',    '$2y$10$TKh8H1.PfuBifdecVpQ2vOuLz4EsZc4l3lhInSVQ9E.ZaVoZVXBZi', 'student'),
  ('Rahul Borah',    'rahul@adbu.in',    '$2y$10$TKh8H1.PfuBifdecVpQ2vOuLz4EsZc4l3lhInSVQ9E.ZaVoZVXBZi', 'student');

-- ============================================================
-- SEED: Sample events
-- ============================================================
INSERT INTO events (title, description, event_date, venue, total_seats, available_seats) VALUES
(
  'TechFest 2025',
  'Annual technology festival featuring hackathons, robotics competitions, paper presentations and workshops by industry experts.',
  '2025-04-12 10:00:00',
  'Main Auditorium, Block A',
  150, 102
),
(
  'Boscotsav Cultural Night',
  'An evening of dance, drama, music and artistic expression celebrating the vibrant culture of ADBU. Open to all departments.',
  '2025-04-18 17:00:00',
  'Open-Air Amphitheatre',
  300, 180
),
(
  'Inter-Department Sports Meet',
  'Compete across cricket, football, badminton, table tennis and athletics with your department team. Prizes for top 3 departments.',
  '2025-04-25 08:00:00',
  'ADBU Sports Ground',
  200, 170
),
(
  'Research Paper Symposium',
  'Undergraduate and postgraduate students present their research work before a panel of faculty judges. Best papers get published.',
  '2025-05-03 09:30:00',
  'Seminar Hall, Block C',
  80, 55
),
(
  'Alumni Connect 2025',
  'Meet and network with ADBU alumni working across industries. Career guidance, mock interviews and internship opportunities.',
  '2025-05-10 11:00:00',
  'Conference Centre',
  120, 90
);

-- ============================================================
-- SEED: Sample registrations (student 2 & 3 registered for events)
-- ============================================================
INSERT INTO registrations (user_id, event_id) VALUES
  (2, 1),
  (2, 2),
  (3, 1),
  (3, 3);

-- ============================================================
-- TRIGGER: auto-decrement available_seats on new registration
-- ============================================================
DELIMITER $$

CREATE TRIGGER trg_after_registration_insert
AFTER INSERT ON registrations
FOR EACH ROW
BEGIN
  UPDATE events
  SET    available_seats = available_seats - 1
  WHERE  id = NEW.event_id
    AND  available_seats > 0;
END$$

-- ============================================================
-- TRIGGER: auto-increment available_seats if registration deleted
-- ============================================================
CREATE TRIGGER trg_after_registration_delete
AFTER DELETE ON registrations
FOR EACH ROW
BEGIN
  UPDATE events
  SET    available_seats = available_seats + 1
  WHERE  id = OLD.event_id
    AND  available_seats < total_seats;
END$$

DELIMITER ;

-- ============================================================
-- DONE! Summary of what was created:
--
--  Tables       : users, events, registrations
--  Admin login  : admin@adbu.in        / admin123
--  Student login: krishna@adbu.in      / student123
--                 priya@adbu.in        / student123
--                 rahul@adbu.in        / student123
--  Events       : 5 sample events seeded
--  Triggers     : seat count auto-managed on register/cancel
-- ============================================================

SELECT id, name, role FROM users;

INSERT INTO users (name, email, password, role)
VALUES ('Krishna', 'admin@gmail.com', '123456', 'admin');

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    event_date DATETIME,
    seats INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Events table (you likely have this already)
CREATE TABLE IF NOT EXISTS events (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(255) NOT NULL,
  description TEXT,
  event_date  DATE NOT NULL,
  seats       INT DEFAULT 50
);
CREATE TABLE registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  event_id INT NOT NULL,
  registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_reg (student_id, event_id)
);
-- Registrations table (NEW - you need this)
CREATE TABLE IF NOT EXISTS registrations (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  event_id      INT NOT NULL,
  registered_at DATETIME DEFAULT NOW(),
  UNIQUE KEY no_duplicate (user_id, event_id)
);


DESC registrations;

ALTER TABLE events 
ADD COLUMN capacity INT(11) DEFAULT 0,
ADD COLUMN location VARCHAR(255) DEFAULT NULL;
