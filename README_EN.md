# Kansei Rental

The **Kansei Rental** web application is for managing vehicle rentals with the Kansei Rental (Japanese Domestic Market) theme. Built using **PHP**, **CSS**, **JS**.

## Features (examples — adjust as needed)

- Vehicle data management (add/update/delete)
- Customer management
- Rental & return transaction recording
- Calculation of total rental costs (based on duration, fines, etc.)
- Admin page & reporting

## Technology

- **PHP** (backend)
- **CSS** (design)
- **JS** (simple logic)
- **Database**: MySQL/MariaDB

## Requirements

- PHP 7.4+ _(adjust as necessary)_
- Web server (Apache/Nginx)
- MySQL/MariaDB
- Git

## How to Run (Local)

1. Clone the repository:

   ```bash
   git clone https://github.com/kuzanf3b/kansei-rental.git
   cd kansei-rental
   ```

2. Prepare the database _(if required)_:
   - Create a database, for example: `rental_jdm`
   - Import SQL file (`rental_jdm.sql`)

3. Configure the database connection:
   - Find the configuration file (`index.php`)
   - Fill in the host, user, password, and database name

4. Run via a web server:
   - If using XAMPP/Laragon/WAMP: place the project folder in `/www` then access:
     - `http://localhost/kansei-rental`
   - Or use the built-in PHP server:

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

## Contribution

Contributions are open. Please:

1. Fork this repo
2. Create a feature branch: `git checkout -b feature/featurename`
3. Commit your changes: `git commit -m "Add feature ..."`
4. Push to the branch: `git push origin feature/featurename`
5. Submit a Pull Request