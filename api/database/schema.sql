CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  academy_name VARCHAR(255) NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(20) NULL,
  birth_date DATE NULL,
  gender VARCHAR(20) NULL,
  cpf VARCHAR(14) NULL,
  address TEXT NULL,
  belt VARCHAR(50) NULL,
  degree VARCHAR(20) NULL,
  last_graduation DATE NULL,
  google_id VARCHAR(255) NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  is_email_verified TINYINT(1) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_users_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_user_id (user_id),
  INDEX idx_expires_at (expires_at)
);

-- Inserir usuário administrador padrão para testes
-- Email: admin@nexora.com
-- Senha: Admin@123
INSERT INTO users (name, email, password_hash, is_email_verified) 
VALUES ('Administrador', 'admin@nexora.com', '$argon2id$v=19$m=65536,t=4,p=1$WVVzY0dJck50VzNkQ29yZQ$2IXEHHNWVmmjYpaUIxELe2INWrMwo01TzXIToF3jUrE', 1);
