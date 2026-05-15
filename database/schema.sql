CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    codigo_acesso VARCHAR(30) NOT NULL UNIQUE,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

CREATE TABLE IF NOT EXISTS chamados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    descricao TEXT NOT NULL,
    prioridade ENUM('Baixa','Media','Alta','Critica') NOT NULL DEFAULT 'Media',
    status ENUM('Aberto','Em andamento','Resolvido','Cancelado') NOT NULL DEFAULT 'Aberto',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS login_tentativas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    sucesso TINYINT(1) NOT NULL DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_email_ip_data (email, ip, criado_em)
);

CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    acao VARCHAR(80) NOT NULL,
    detalhes VARCHAR(255) NULL,
    ip VARCHAR(45) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Empresas de teste
INSERT INTO empresas (nome, codigo_acesso) VALUES
('Empresa Alpha',    'ALPHA2026'),
('Empresa Beta',     'BETA2026'),
('Empresa Sigma',    'SIGMA2026'),
('Empresa Delta',    'DELTA2026'),
('HelpIT Suporte',   'ADMIN2026')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), ativo = 1;
