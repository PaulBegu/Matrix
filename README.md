# Matrix Application

Aplicație PHP cu sistem de autentificare integrat cu Navigator (via sys_users).

## Structura Folderelor

```
Matrix/
├── config/
│   ├── database.php    # Conexiune la baza de date SMW
│   └── config.json     # Configurări aplicație
├── public/
│   └── index.php       # Entry point (pagina principală)
├── src/
│   ├── common.php      # Funcții utilitare comune
│   └── auth.php        # Funcții de autentificare
├── logs/               # Fișiere de log
└── README.md
```

## Utilizare

### Autentificare

Aplicația preia `user_id` din parametrul GET și îl memorează în sesiune:

```
http://server/Matrix/public/index.php?user_id=534
```

### Integrare cu Navigator

La fel ca docai, aplicația se integrează cu Navigator prin transmiterea user_id în URL.
Numele utilizatorului se preia din tabela `sys_users` (baza SMW).

## Configurare

Editează `config/database.php` pentru a modifica credențialele de conectare la baza de date.
