# Admin → Games module for codingcush.com

Source of the "Games" section of the CodingCush admin panel (codingcush.com/admin/games).
These files belong to the codingcush.com site (domains/codingcush.com/public_html), not to the games site:
the paths below are relative to that folder. They are kept here so the module has version history.

- app/Models/GamesData.php: opens the games database (games-data/games.sqlite, or MySQL from games/api/config.php)
- app/Controllers/Admin/GamesController.php, views/admin/games/*, public/assets/css/games-admin.css
- config/routes.php, app/Core/Permissions.php, views/layouts/admin.php: the site's own files with the Games routes,
  the games.manage permission and the sidebar dropdown added (copies as of 17 Nov 2026)

This folder is not served by the games site (.htaccess answers 404 for /_src).
