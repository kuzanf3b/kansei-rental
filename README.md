# Kansei Rental

The **Kansei Rental** web application is designed for managing vehicle rentals with a focus on Kansei Rental (Japanese Domestic Market). Built using **PHP**, **CSS**, and **JS**.

## Features (example — adjust accordingly)

- Vehicle data management (add/edit/delete)
- Customer management
- Rental & return transaction recording
- Total rental cost calculation (based on duration, penalties, etc.)
- Admin dashboard & reporting

## Technologies

- **PHP** (backend)
- **CSS** (frontend)
- **JS** (simple logic)
- **Database**: MySQL/MariaDB

## Requirements

- PHP 7.4+ _(adjust to the version used)_
- Web server (Apache/Nginx)
- MySQL/MariaDB
- Git

## How to Run (Local)

1. Clone the repository:

   ```bash
   git clone https://github.com/kuzanf3b/kansei-rental.git
   cd kansei-rental
   ```

2. Set up the database _(if applicable):_
   - Create a database, e.g., `rental_jdm`
   - Import the SQL file (`rental_jdm.sql`)

3. Configure the database connection:
   - Locate the configuration file (`index.php`)
   - Fill in the host, user, password, and database name

4. Run via a web server:
   - If using XAMPP/Laragon/WAMP: place the project folder in `/www` and access:
     - `http://localhost/kansei-rental`
   - Or use PHP's built-in server:

     ```bash
     php -S localhost:8000
     ```

     Then open `http://localhost:8000`

## Folder Structure (optional)

- `assets/css` — CSS folder
- `assets/js` — JS folder
- `pages/` — application pages
- `index.php` — application entry point

## Login Accounts (optional)

- Admin: `admin / admin`
- User: `user / user`

## Contributing

Contributions are welcome. Please:

1. Fork this repository
2. Create a feature branch: `git checkout -b feature/featurename`
3. Commit your changes: `git commit -m "Add feature ..."`
4. Push to the branch: `git push origin feature/featurename`
5. Create a Pull Request