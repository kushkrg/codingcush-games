<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Role permission matrix.
 *
 * The permission catalogue and the factory defaults live in this class.
 * The super admin's choices are stored in the `role_permissions` table
 * (created on demand) and override the defaults key by key. The
 * super_admin role itself is never stored: it always has every
 * permission (enforced in Auth::can), so the super admin can never be
 * locked out.
 *
 * Every database touch is guarded: if storage fails, the factory
 * defaults apply and the site keeps running.
 */
class Permissions
{
    /** Roles the matrix manages (super_admin is implicit full access). */
    public const ROLES = ['admin', 'editor', 'author', 'contributor'];

    private static bool $ensured = false;
    private static ?array $overrides = null;

    /** Permission catalogue: key => [label, group]. Ordered by group. */
    public static function catalogue(): array
    {
        return [
            'posts.manage_own'   => ['Manage own posts (create, edit, delete)', 'Posts'],
            'posts.manage_any'   => ['Manage all posts (any author)', 'Posts'],
            'media.manage_own'   => ['Media library, upload, own uploads', 'Media'],
            'media.manage_any'   => ['Manage all media (any uploader)', 'Media'],
            'categories.manage'  => ['Manage categories', 'Content'],
            'tags.manage'        => ['Manage tags', 'Content'],
            'comments.moderate'  => ['Moderate comments', 'Content'],
            'pages.manage'       => ['Manage pages', 'Content'],
            'menus.manage'       => ['Manage menus', 'Content'],
            'messages.manage'    => ['View contact messages', 'Audience & Site'],
            'subscribers.manage' => ['Manage subscribers and email', 'Audience & Site'],
            'students.manage'    => ['Manage Students module', 'Audience & Site'],
            'tools.use'          => ['Use the tools manager', 'Audience & Site'],
            'plugins.manage'     => ['Manage WordPress plugins and view their stats', 'Audience & Site'],
            'games.manage'       => ['Manage CodingCush Games (players, analytics, settings)', 'Audience & Site'],
            'redirects.manage'   => ['Manage redirects', 'Audience & Site'],
            'analytics.view'     => ['View analytics', 'Audience & Site'],
            'users.manage'       => ['Manage admin users', 'Administration'],
            'settings.manage'    => ['Change site settings', 'Administration'],
            'store.manage'       => ['Manage My Store', 'Administration'],
            'ads.manage'         => ['Manage ads', 'Administration'],
            'permissions.manage' => ['Manage role permissions', 'Administration'],
        ];
    }

    /** Factory defaults per role: permission => true (absent = denied). */
    public static function defaults(): array
    {
        $adminBase = [
            'posts.manage_own'  => true,
            'posts.manage_any'  => true,
            'media.manage_own'  => true,
            'media.manage_any'  => true,
            'categories.manage' => true,
            'tags.manage'       => true,
            'comments.moderate' => true,
            'pages.manage'      => true,
            'menus.manage'      => true,
            'messages.manage'   => true,
            'analytics.view'    => true,
        ];

        return [
            'admin' => $adminBase,
            'editor' => $adminBase + [
                'students.manage'  => true,
                'tools.use'        => true,
                'redirects.manage' => true,
            ],
            'author' => [
                'posts.manage_own' => true,
                'media.manage_own' => true,
                'analytics.view'   => true,
            ],
            'contributor' => [
                'analytics.view' => true,
            ],
        ];
    }

    /** Effective permission map for a role (defaults + stored overrides). */
    public static function forRole(string $role): array
    {
        $defaults = self::defaults()[$role] ?? [];
        $map = [];
        foreach (self::catalogue() as $key => $_) {
            $map[$key] = !empty($defaults[$key]);
        }

        $stored = self::overrides();
        if (isset($stored[$role])) {
            foreach ($stored[$role] as $key => $allowed) {
                if (array_key_exists($key, $map)) {
                    $map[$key] = $allowed;
                }
            }
        }

        return $map;
    }

    public static function can(string $role, string $permission): bool
    {
        $map = self::forRole($role);
        return $map[$permission] ?? false;
    }

    /** Persist the full matrix for one role. */
    public static function saveRole(string $role, array $map): void
    {
        if (!in_array($role, self::ROLES, true)) {
            return;
        }
        self::ensure();
        try {
            Database::query("DELETE FROM role_permissions WHERE role = :role", ['role' => $role]);
            foreach (self::catalogue() as $key => $_) {
                Database::query(
                    "INSERT INTO role_permissions (role, permission, allowed) VALUES (:role, :perm, :allowed)",
                    ['role' => $role, 'perm' => $key, 'allowed' => !empty($map[$key]) ? 1 : 0]
                );
            }
            self::$overrides = null;
        } catch (\Throwable $e) {
            // Best effort: the permissions page re-reads and shows the truth.
        }
    }

    /** Drop a role's stored overrides so the factory defaults apply again. */
    public static function resetRole(string $role): void
    {
        if (!in_array($role, self::ROLES, true)) {
            return;
        }
        self::ensure();
        try {
            Database::query("DELETE FROM role_permissions WHERE role = :role", ['role' => $role]);
            self::$overrides = null;
        } catch (\Throwable $e) {
            // Best effort, same as saveRole().
        }
    }

    /** Stored overrides: role => permission => bool (cached per request). */
    private static function overrides(): array
    {
        if (self::$overrides !== null) {
            return self::$overrides;
        }
        self::$overrides = [];
        self::ensure();
        try {
            $rows = Database::query("SELECT role, permission, allowed FROM role_permissions")->fetchAll();
            foreach ($rows ?: [] as $row) {
                self::$overrides[(string) $row['role']][(string) $row['permission']] = ((int) $row['allowed']) === 1;
            }
        } catch (\Throwable $e) {
            self::$overrides = [];
        }
        return self::$overrides;
    }

    /** Create the storage table on first use (idempotent, best effort). */
    private static function ensure(): void
    {
        if (self::$ensured) {
            return;
        }
        self::$ensured = true;
        try {
            Database::query(
                "CREATE TABLE IF NOT EXISTS role_permissions (
                    role VARCHAR(50) NOT NULL,
                    permission VARCHAR(100) NOT NULL,
                    allowed TINYINT NOT NULL DEFAULT 0,
                    PRIMARY KEY (role, permission)
                )"
            );
        } catch (\Throwable $e) {
            // Defaults keep the site running even if this fails.
        }
    }
}
