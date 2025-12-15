-- Apagar a base de dados caso já exista
DROP DATABASE IF EXISTS fitness_app;

-- Criar a base de dados
CREATE DATABASE fitness_app
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

-- Selecionar a base de dados
USE fitness_app;

-- =========================
-- TABELA UTILIZADOR
-- =========================
CREATE TABLE IF NOT EXISTS Utilizador (
    id_utilizador INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    foto_perfil VARCHAR(255),
    biografia TEXT,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    genero ENUM('M','F','Outro'),
    altura DECIMAL(5,2),
    peso_atual DECIMAL(5,2),
    data_nascimento DATE
);

-- =========================
-- TABELA TREINO (Rotina)
-- =========================
CREATE TABLE IF NOT EXISTS Treino (
    id_treino INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizador INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    objetivo VARCHAR(150),
    data_criacao DATE NOT NULL DEFAULT (CURRENT_DATE),
    privado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_utilizador) REFERENCES Utilizador(id_utilizador) ON DELETE CASCADE
);

-- =========================
-- TABELA EXERCICIO (Catálogo)
-- =========================
CREATE TABLE IF NOT EXISTS Exercicio (
    id_exercicio INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    grupo_muscular VARCHAR(50) NOT NULL,
    musculo_principal VARCHAR(100),
    musculo_secundario VARCHAR(100),
    equipamento VARCHAR(50) NOT NULL
);

-- ==========================================================
-- TABELA TREINO_EXERCICIO (LIGAÇÃO E DETALHES DA ROTINA)
-- ==========================================================
CREATE TABLE IF NOT EXISTS Treino_Exercicio (
    id_treino INT NOT NULL,
    id_exercicio INT NOT NULL,
    
    ordem INT NOT NULL,          
    sets_sugeridos INT NOT NULL DEFAULT 3, 
    nota_planeada TEXT,          
    tempo_descanso_seg INT,      
    
    PRIMARY KEY (id_treino, id_exercicio, ordem),
    FOREIGN KEY (id_treino) REFERENCES Treino(id_treino) ON DELETE CASCADE,
    FOREIGN KEY (id_exercicio) REFERENCES Exercicio(id_exercicio) ON DELETE CASCADE
);

-- =========================
-- TABELA DIETA
-- =========================
CREATE TABLE IF NOT EXISTS Dieta (
    id_dieta INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizador INT NOT NULL,
    calorias_totais INT,
    hidratos DECIMAL(6,2),
    gordura DECIMAL(6,2),
    FOREIGN KEY (id_utilizador) REFERENCES Utilizador(id_utilizador) ON DELETE CASCADE
);

-- =========================
-- TABELA REFEICAO
-- =========================
CREATE TABLE IF NOT EXISTS Refeicao (
    id_refeicao INT AUTO_INCREMENT PRIMARY KEY,
    id_dieta INT NOT NULL,
    nome VARCHAR(100),
    horario TIME,
    FOREIGN KEY (id_dieta) REFERENCES Dieta(id_dieta) ON DELETE CASCADE
);

-- =========================
-- TABELA ALIMENTO
-- =========================
CREATE TABLE IF NOT EXISTS Alimento (
    id_alimento INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    kcal INT,
    hidratos DECIMAL(6,2),
    proteinas DECIMAL(6,2),
    gordura DECIMAL(6,2)
);

-- =========================
-- TABELA REFEICAO_ALIMENTO
-- =========================
CREATE TABLE IF NOT EXISTS Refeicao_Alimento (
    id_refeicao INT NOT NULL,
    id_alimento INT NOT NULL,
    PRIMARY KEY (id_refeicao, id_alimento),
    FOREIGN KEY (id_refeicao) REFERENCES Refeicao(id_refeicao) ON DELETE CASCADE,
    FOREIGN KEY (id_alimento) REFERENCES Alimento(id_alimento) ON DELETE CASCADE
);

-- =========================
-- INSERTS DE EXERCICIOS
-- =========================
INSERT INTO Exercicio (nome, grupo_muscular, musculo_principal, musculo_secundario, equipamento) VALUES
( 'Supino Reto','Peito','Peitoral Maior','Tríceps, Deltoide Anterior', 'Barra'),
( 'Supino Inclinado','Peito','Peitoral Superior','Tríceps, Deltoide Anterior', 'Halteres'),
( 'Supino Declinado','Peito','Peitoral Inferior','Tríceps', 'Máquina'),
( 'Crucifixo com Halteres','Peito','Peitoral Maior','Deltoide Anterior', 'Halteres'),
( 'Flexões','Peito','Peitoral Maior','Tríceps, Ombros, Core', 'Corpo'),
( 'Puxada na Frente','Costas','Dorsais','Bíceps, Deltoide Posterior', 'Máquina'),
( 'Remada Curvada','Costas','Dorsais','Bíceps, Trapézio', 'Barra'),
( 'Remada Baixa','Costas','Dorsais','Bíceps', 'Máquina'),
( 'Levantamento Terra','Costas','Costas','Glúteos, Isquiotibiais, Lombar', 'Barra'),
( 'Pull Over','Costas','Dorsais','Peitoral, Tríceps', 'Halteres'),
( 'Desenvolvimento Militar','Ombros','Deltoide Anterior','Tríceps', 'Barra'),
( 'Elevação Lateral','Ombros','Deltoide Lateral','Trapézio', 'Halteres'),
( 'Elevação Frontal','Ombros','Deltoide Anterior','Peitoral Superior', 'Halteres'),
( 'Face Pull','Ombros','Deltoide Posterior','Trapézio', 'Cabo'),
( 'Rosca Direta','Braços','Bíceps','Antebraços', 'Barra'),
( 'Rosca Alternada','Braços','Bíceps','Antebraços', 'Halteres'),
( 'Rosca Martelo','Braços','Bíceps','Braquial', 'Halteres'),
( 'Tríceps Testa','Braços','Tríceps','Ombros', 'Barra'),
( 'Tríceps Corda','Braços','Tríceps','Antebraços', 'Cabo'),
( 'Mergulhos','Braços','Tríceps','Peitoral, Ombros', 'Corpo'),
( 'Agachamento Livre','Pernas','Quadríceps','Glúteos, Isquiotibiais, Core', 'Barra'),
( 'Leg Press','Pernas','Quadríceps','Glúteos, Isquiotibiais', 'Máquina'),
( 'Extensão de Pernas','Pernas','Quadríceps','Nenhum', 'Máquina'),
( 'Flexão de Pernas','Pernas','Isquiotibiais','Glúteos', 'Máquina'),
( 'Afundos','Pernas','Quadríceps','Glúteos, Isquiotibiais', 'Halteres'),
( 'Stiff','Pernas','Isquiotibiais','Glúteos, Lombar', 'Barra'),
( 'Elevação de Gémeos','Pernas','Gémeos','Sóleo', 'Máquina'),
( 'Prancha','Core','Core','Ombros, Glúteos', 'Corpo'),
( 'Crunch Abdominal','Core','Reto Abdominal','Oblíquos', 'Corpo'),
( 'Elevação de Pernas','Core','Abdominais Inferiores','Flexores da Anca', 'Corpo'),
( 'Russian Twist','Core','Oblíquos','Reto Abdominal', 'Halteres');
