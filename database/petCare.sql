CREATE DATABASE IF NOT EXISTS petCareDB;
USE petCareDB;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT true,
    reset_token VARCHAR(64) NULL,
    reset_expiry TIMESTAMP NULL
);

INSERT INTO users (email, password_hash, role) 
VALUES ('admin@petcare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');



CREATE TABLE user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone_number VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    avatar_url VARCHAR(255),
    bio TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE(user_id)
);

CREATE TABLE pets (
    pet_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT,
    name VARCHAR(50) NOT NULL,
    pet_type ENUM('dog', 'cat', 'bird', 'fish', 'small_animal', 'reptile', 'other') NOT NULL,
    breed VARCHAR(100),
    age_years SMALLINT,
    age_months SMALLINT,
    gender ENUM('male', 'female', 'unknown'),
    weight_kg DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE food_guides (
    guide_id INT AUTO_INCREMENT PRIMARY KEY,
    pet_type ENUM('dog', 'cat', 'bird', 'fish', 'small_animal', 'reptile', 'other') NOT NULL,
    age_range_start_months INT NOT NULL,
    age_range_end_months INT,
    weight_range_start_kg DECIMAL(5,2),
    weight_range_end_kg DECIMAL(5,2),
    food_type VARCHAR(100) NOT NULL,
    portion_size_grams INT NOT NULL,
    meals_per_day INT NOT NULL,
    feeding_instructions TEXT NOT NULL,
    nutritional_info TEXT NOT NULL,
    special_notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);


CREATE TABLE care_guides (
    guide_id INT AUTO_INCREMENT PRIMARY KEY,
    pet_type ENUM('dog', 'cat', 'bird', 'fish', 'small_animal', 'reptile', 'other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    tips TEXT,
    category VARCHAR(100) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);


CREATE TABLE medical_consultations (
    consultation_id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT,
    vet_id INT,
    consultation_date TIMESTAMP NOT NULL,
    main_symptoms TEXT NOT NULL,
    symptoms_details TEXT,
    diagnosis TEXT,
    treatment_plan TEXT,
    follow_up_date TIMESTAMP NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(pet_id) ON DELETE CASCADE,
    FOREIGN KEY (vet_id) REFERENCES users(user_id)
);

CREATE TABLE vaccination_logs (
    vaccination_id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT,
    pet_id INT,
    vaccine_name VARCHAR(255) NOT NULL,
    vaccination_date TIMESTAMP NOT NULL,
    next_due_date TIMESTAMP,
    administered_by INT,
    batch_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id) REFERENCES medical_consultations(consultation_id),
    FOREIGN KEY (pet_id) REFERENCES pets(pet_id) ON DELETE CASCADE,
    FOREIGN KEY (administered_by) REFERENCES users(user_id)
);

CREATE TABLE training_tips (
    tip_id INT AUTO_INCREMENT PRIMARY KEY,
    pet_type ENUM('dog', 'cat', 'bird', 'fish', 'small_animal', 'reptile', 'other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    difficulty_level VARCHAR(20),
    estimated_duration VARCHAR(50),
    prerequisites JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);


CREATE TABLE community_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    tags JSON,
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    is_pinned BOOLEAN DEFAULT false,
    is_archived BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE community_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT,
    user_id INT,
    content TEXT NOT NULL,
    parent_comment_id INT,
    likes_count INT DEFAULT 0,
    is_edited BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES community_comments(comment_id) ON DELETE CASCADE
);

CREATE TABLE community_likes (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_pets_owner ON pets(owner_id);
CREATE INDEX idx_food_guides_pet_type ON food_guides(pet_type);
CREATE INDEX idx_consultations_pet ON medical_consultations(pet_id);
CREATE INDEX idx_consultations_date ON medical_consultations(consultation_date);
CREATE INDEX idx_vaccinations_pet ON vaccination_logs(pet_id);
CREATE INDEX idx_community_posts_user ON community_posts(user_id);
CREATE INDEX idx_community_posts_created ON community_posts(created_at);
CREATE INDEX idx_community_comments_post ON community_comments(post_id);
CREATE INDEX idx_training_tips_pet_type ON training_tips(pet_type);
