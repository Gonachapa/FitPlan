CREATE DATABASE IF NOT EXISTS fitness_app
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE fitness_app;

CREATE TABLE Utilizador (
    id_utilizador INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    foto_perfil VARCHAR(255),
    biografia TEXT,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    genero ENUM('M', 'F', 'Outro'),
    altura DECIMAL(5,2),
    peso_atual DECIMAL(5,2),
    data_nascimento DATE
);

CREATE TABLE Treino (
    id_treino INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizador INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    objetivo VARCHAR(150),
    data_criacao DATE DEFAULT CURRENT_DATE,
    privado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_utilizador) REFERENCES Utilizador(id_utilizador) ON DELETE CASCADE
);

CREATE TABLE Exercicio (
    id_exercicio INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    musculo_principal VARCHAR(100),
    musculo_secundario VARCHAR(100)
);

CREATE TABLE Treino_Exercicio (
    id_treino INT NOT NULL,
    id_exercicio INT NOT NULL,
    PRIMARY KEY (id_treino, id_exercicio),
    FOREIGN KEY (id_treino) REFERENCES Treino(id_treino) ON DELETE CASCADE,
    FOREIGN KEY (id_exercicio) REFERENCES Exercicio(id_exercicio) ON DELETE CASCADE
);

CREATE TABLE Dieta (
    id_dieta INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizador INT NOT NULL,
    calorias_totais INT,
    hidratos DECIMAL(6,2),
    gordura DECIMAL(6,2),
    FOREIGN KEY (id_utilizador) REFERENCES Utilizador(id_utilizador) ON DELETE CASCADE
);

CREATE TABLE Refeicao (
    id_refeicao INT AUTO_INCREMENT PRIMARY KEY,
    id_dieta INT NOT NULL,
    nome VARCHAR(100),
    horario TIME,
    FOREIGN KEY (id_dieta) REFERENCES Dieta(id_dieta) ON DELETE CASCADE
);

CREATE TABLE Alimento (
    id_alimento INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    kcal INT,
    hidratos DECIMAL(6,2),
    proteinas DECIMAL(6,2),
    gordura DECIMAL(6,2)
);

CREATE TABLE Refeicao_Alimento (
    id_refeicao INT NOT NULL,
    id_alimento INT NOT NULL,
    PRIMARY KEY (id_refeicao, id_alimento),
    FOREIGN KEY (id_refeicao) REFERENCES Refeicao(id_refeicao) ON DELETE CASCADE,
    FOREIGN KEY (id_alimento) REFERENCES Alimento(id_alimento) ON DELETE CASCADE
);