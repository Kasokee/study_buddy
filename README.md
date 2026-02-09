SQL COMMAND

-- USERS TABLE
CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'tutor', 'admin') NOT NULL DEFAULT 'student',
    subject VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved') NOT NULL DEFAULT 'approved',
    otp_code VARCHAR(6) DEFAULT NULL,
    otp_expires DATETIME DEFAULT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_email (email)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- SCHEDULE TABLE
CREATE TABLE schedule (
    id INT(11) NOT NULL AUTO_INCREMENT,
    tutor_id INT(11) NOT NULL,
    available_day ENUM(
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday'
    ) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_tutor_id (tutor_id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


--BOOKINGS TABLE
CREATE TABLE bookings (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    schedule_id INT(11) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_student_id (student_id),
    INDEX idx_schedule_id (schedule_id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;
