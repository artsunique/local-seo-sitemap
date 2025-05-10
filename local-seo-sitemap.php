<?php

/**
 * Plugin Name: Lokale SEO Sitemap Manager
 * Description: Verwaltet lokale SEO-Seiten & erzeugt XML-Sitemap-Einträge.
 * Version: 2.0
 * Author: Andreas Grafiker
 */

register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lokale_seo_pages';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        title TEXT NOT NULL,
        slug VARCHAR(255) NOT NULL,
        meta_title TEXT DEFAULT '',
        meta_description TEXT DEFAULT '',
        PRIMARY KEY (id)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Neue Tabelle für Snippets anlegen
    $snippets_table = $wpdb->prefix . 'lokale_seo_snippets';
    $wpdb->query("CREATE TABLE IF NOT EXISTS $snippets_table (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        content LONGTEXT NOT NULL,
        PRIMARY KEY (id)
    ) $charset;");

    // Prüfe, ob Spalten fehlen, und ergänze sie bei Bedarf
    $columns = $wpdb->get_col("DESC $table_name", 0);
    if (!in_array('meta_title', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN meta_title TEXT DEFAULT ''");
    }
    if (!in_array('meta_description', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN meta_description TEXT DEFAULT ''");
    }
    if (!in_array('snippet_id', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN snippet_id MEDIUMINT(9) DEFAULT NULL");
    }

    // lokales View-Template erzeugen
    $themePath = get_theme_file_path('/resources/views/lokale-seo.blade.php');
    if (!file_exists($themePath)) {
        $template = <<<BLADE
<!doctype html>
<html @php(language_attributes())>

<head>
    <title>{{ \$entry->meta_title }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ \$entry->meta_description }}">

    <link rel="canonical" href="{{ request()->fullUrl() }}" />

    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ \$entry->meta_title }}" />
    <meta property="og:description" content="{{ \$entry->meta_description }}" />
    <meta property="og:url" content="{{ request()->fullUrl() }}" />
    <meta property="og:site_name" content="{{ get_bloginfo('name') }}" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ \$entry->meta_title }}" />
    <meta name="twitter:description" content="{{ \$entry->meta_description }}" />
    <meta name="author" content="ANB" />
    <meta name="copyright" content="{{ date('Y') }} ANB" />
    <meta name="language" content="de" />
    <meta name="revisit-after" content="10 days" />
    <meta name="Classification" content="Business" />
    <meta name="designer" content="Andreas Burget" />
    <meta name="publisher" content="ANB" />
    <meta name="owner" content="ANB" />
    <meta name="url" content="{{ request()->fullUrl() }}" />
    <meta name="identifier-URL" content="{{ request()->fullUrl() }}" />
    <meta name="coverage" content="Worldwide" />
    <meta name="distribution" content="Global" />
    <meta name="rating" content="General" />
    <meta name="robots" content="index,follow" />
    <meta name="googlebot" content="index,follow" />
    <meta name="msnbot" content="index,follow" />
    <meta name="allow-search" content="yes" />
    <meta name="page-topic" content="{{ \$entry->meta_title }}" />
    <meta name="audience" content="all" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#000000" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      "name": "{{ \$entry->title }}",
      "url": "{{ request()->fullUrl() }}",
      "description": "{{ \$entry->meta_description }}",
      "image": "https://niggemann-brunner.test/path/zum/logo.jpg",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Musterstraße 1",
        "addressLocality": "München",
        "postalCode": "80331",
        "addressCountry": "DE"
      },
      "telephone": "+49 89 1234567"
    }
    </script>
    @php(do_action('get_header'))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body @php(body_class())>
    @php(wp_body_open())

    <div id="app">
        <a class="sr-only focus:not-sr-only" href="#main">
            {{ __('Skip to content', 'sage') }}
        </a>

        @include('sections.header')

        <x-main>
            <h1>{{ \$entry->title }}</h1>
            <div class="container mx-auto pt-40">{!! \$content !!}</div>
        </x-main>

        @include('sections.footer')
    </div>

    @php(do_action('get_footer'))
    @php(wp_footer())
</body>

</html>
BLADE;

        file_put_contents($themePath, $template);
    }
});

add_action('admin_menu', function () {
    add_menu_page('Lokale SEO Seiten', 'Lokale SEO', 'manage_options', 'lokale-seo', 'lokale_seo_admin_page');
    add_submenu_page('lokale-seo', 'Textbausteine', 'Textbausteine', 'manage_options', 'lokale-seo-snippets', 'lokale_seo_snippets_page');
    add_submenu_page('lokale-seo', 'Anleitung', 'HowTo', 'manage_options', 'lokale-seo-howto', 'lokale_seo_howto_page');
});
function lokale_seo_howto_page()
{
?>
    <div class="wrap">
        <h1>Lokale SEO Plugin – Anleitung</h1>
        <p>Willkommen zur Anleitung des Lokale SEO Sitemap Managers. Hier finden Sie Hinweise zur Nutzung:</p>

        <h2>1. Lokale Landingpages anlegen</h2>
        <p>Verwenden Sie das Eingabefeld unter „Lokale SEO“, um neue Seiten wie „Badsanierung München“ anzulegen. Die Slug-URL wird automatisch generiert.</p>

        <h2>2. Meta-Daten pflegen</h2>
        <p>Klicken Sie auf „Details“, um <strong>Meta Title</strong> und <strong>Meta Description</strong> zu bearbeiten. Die Zeichenzahl wird angezeigt.</p>

        <h2>3. Textbausteine erstellen</h2>
        <p>Gehen Sie zu <strong>Textbausteine</strong>, um wiederverwendbare Inhalte mit dem Platzhalter <code>@@keyword@@</code> zu hinterlegen. Dieser wird durch den jeweiligen Seitentitel ersetzt.</p>

        <h2>4. Sitemap erzeugen</h2>
        <p>Markieren Sie Einträge und klicken Sie auf „Zur Sitemap“. Die Datei wird unter <code>/lokale-sitemap.xml</code> abgelegt.</p>

        <h2>5. Seitenansicht</h2>
        <p>Wenn ein Eintrag in der Sitemap vorhanden ist, erscheint der Button „Seite ansehen“.</p>

        <h2>6. CSV-Import</h2>
        <p>Nutzen Sie die rechte Spalte unter „Lokale SEO“, um Inhalte gesammelt zu importieren. Format:</p>
        <pre>title,meta_title,meta_description
Badsanierung München,Badsanierung München | ANB,Badsanierung München: Professionelle Komplettsanierung...</pre>
    </div>
<?php
}

function lokale_seo_snippets_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'lokale_seo_snippets';
    $message = null;

    // Tabelle anlegen, falls nicht vorhanden
    $wpdb->query("CREATE TABLE IF NOT EXISTS $table (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        content LONGTEXT NOT NULL,
        PRIMARY KEY (id)
    ) DEFAULT CHARSET=utf8mb4;");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $content = wp_kses_post($_POST['snippet_content']);
        $id = isset($_POST['snippet_id']) ? intval($_POST['snippet_id']) : 0;

        if ($id > 0) {
            $wpdb->update($table, ['content' => $content], ['id' => $id]);
            $message = ['type' => 'success', 'text' => 'Textbaustein aktualisiert.'];
        } else {
            $wpdb->insert($table, ['content' => $content]);
            $message = ['type' => 'success', 'text' => 'Textbaustein gespeichert.'];
        }
    }

    $snippets = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    $edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
    $edit_content = '';

    if ($edit_id > 0) {
        $row = $wpdb->get_row($wpdb->prepare("SELECT content FROM $table WHERE id = %d", $edit_id));
        if ($row) {
            $edit_content = $row->content;
        }
    }
?>
    <div class="wrap">
        <h1>Textbausteine verwalten</h1>
        <p style="max-width: 700px;">
            Hier verwalten Sie zentrale Textbausteine für lokale SEO-Landingpages. Nutzen Sie den <strong>@@keyword@@</strong>-Platzhalter, um automatisch den jeweiligen Eintragstitel (z. B. „Badsanierung München“) einzufügen. Medien können direkt über den Editor eingebunden werden.
        </p>

        <?php if ($message): ?>
            <div class="notice notice-<?= esc_attr($message['type']); ?> is-dismissible">
                <p><?= esc_html($message['text']); ?></p>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="snippet_id" value="<?= esc_attr($edit_id); ?>">
            <?php
            wp_editor($edit_content, 'snippet_content', [
                'textarea_name' => 'snippet_content',
                'media_buttons' => true,
                'textarea_rows' => 40,
            ]);
            ?>
            <p><input type="submit" class="button button-primary" value="<?= $edit_id ? 'Aktualisieren' : 'Textbaustein speichern'; ?>"></p>
        </form>

        <?php if ($edit_content): ?>
            <h2>SEO-Strukturanalyse</h2>
            <?php
            $tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'a', 'strong', 'em', 'ul', 'ol', 'li', 'blockquote', 'img'];
            echo '<ul>';
            foreach ($tags as $tag) {
                preg_match_all('/<' . $tag . '\b[^>]*>/i', $edit_content, $matches);
                echo '<li><strong>&lt;' . $tag . '&gt;</strong>: ' . count($matches[0]) . '</li>';
            }
            echo '</ul>';
            ?>
        <?php endif; ?>

        <h2>Vorhandene Bausteine</h2>
        <ul>
            <?php foreach ($snippets as $snippet): ?>
                <li>
                    <code>ID: <?= $snippet->id; ?></code> –
                    <?= wp_trim_words(wp_strip_all_tags($snippet->content), 20); ?>
                    [<a href="<?= admin_url('admin.php?page=lokale-seo-snippets&edit=' . $snippet->id); ?>">Bearbeiten</a>]
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php
}

function lokale_seo_admin_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'lokale_seo_pages';
    $message = null;

    // Alle Snippets laden
    $snippets = $wpdb->get_results("SELECT id, content FROM {$wpdb->prefix}lokale_seo_snippets ORDER BY id DESC");

    // Neue Zeile für Einzel-Meta-Daten speichern
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSV-Import
        if (isset($_POST['import_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            if ($handle) {
                $headers = fgetcsv($handle, 1000, ',');
                $imported = 0;
                while (!feof($handle)) {
                    $data = fgetcsv($handle, 1000, ',');
                    if ($data === false || count($data) !== count($headers)) {
                        continue; // Zeile überspringen, wenn leer oder Spaltenanzahl ungleich
                    }

                    $row = array_combine($headers, $data);
                    if (empty($row['title'])) continue;

                    $title = sanitize_text_field($row['title']);
                    $slug = sanitize_title($title);
                    $meta_title = sanitize_text_field($row['meta_title'] ?? '');
                    $meta_description = sanitize_textarea_field($row['meta_description'] ?? '');

                    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE slug = %s", $slug));
                    if (!$exists) {
                        if ($wpdb->insert($table, [
                            'title' => $title,
                            'slug' => $slug,
                            'meta_title' => $meta_title,
                            'meta_description' => $meta_description,
                        ])) {
                            $imported++;
                        }
                    }
                }
                fclose($handle);
                $message = ['type' => 'success', 'text' => "$imported Eintrag/Einträge erfolgreich importiert."];
            } else {
                $message = ['type' => 'error', 'text' => 'Fehler beim Öffnen der Datei.'];
            }
        }
        if (isset($_POST['save_meta_single'])) {
            $id = (int) $_POST['save_meta_single'];
            $meta_title = sanitize_text_field($_POST['meta_title'][$id] ?? '');
            $meta_description = sanitize_textarea_field($_POST['meta_description'][$id] ?? '');
            $snippet_id = isset($_POST['snippet_id'][$id]) ? (int)$_POST['snippet_id'][$id] : null;
            $wpdb->update($table, [
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'snippet_id' => $snippet_id
            ], ['id' => $id]);
            $message = ['type' => 'success', 'text' => 'Meta-Daten gespeichert.'];
        }

        // Ausgewählte löschen
        if (isset($_POST['delete_selected']) && !empty($_POST['selected'])) {
            $ids = array_map('intval', $_POST['selected']);
            foreach ($ids as $id) {
                $wpdb->delete($table, ['id' => $id]);
            }

            // Sitemap aktualisieren (nur übrig gebliebene)
            $remaining = $wpdb->get_results("SELECT slug FROM $table ORDER BY id DESC");
            $site_url = get_site_url();
            $sitemap_path = dirname(WP_CONTENT_DIR) . '/lokale-sitemap.xml';
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

            foreach ($remaining as $r) {
                $url = $site_url . '/' . $r->slug;
                $entry = $xml->addChild('url');
                $entry->addChild('loc', $url);
                $entry->addChild('lastmod', date('Y-m-d'));
                $entry->addChild('changefreq', 'monthly');
                $entry->addChild('priority', '0.8');
            }

            $xml->asXML($sitemap_path);
            $message = ['type' => 'success', 'text' => count($ids) . ' Eintrag/Einträge gelöscht und Sitemap aktualisiert.'];
        }

        // Löschen aller Einträge + Sitemap
        if (isset($_POST['delete_all'])) {
            $wpdb->query("DELETE FROM $table");
            $sitemap = dirname(WP_CONTENT_DIR) . '/lokale-sitemap.xml';
            if (file_exists($sitemap)) {
                unlink($sitemap);
            }
            $message = ['type' => 'success', 'text' => 'Alle Einträge und die Sitemap wurden gelöscht.'];
        }

        // Eintrag speichern
        if (isset($_POST['seitentitel']) && !empty($_POST['seitentitel'])) {
            $title = sanitize_text_field($_POST['seitentitel']);
            $slug = sanitize_title($title);

            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE slug = %s", $slug));
            if (!$exists) {
                $wpdb->insert($table, ['title' => $title, 'slug' => $slug]);

                $site_name = get_bloginfo('name');
                $meta_title = $title . ' | ' . $site_name;
                $meta_description = $title . ': Platzhalterbeschreibung für lokale SEO-Zwecke.';
                $wpdb->update($table, [
                    'meta_title' => $meta_title,
                    'meta_description' => $meta_description
                ], ['slug' => $slug]);

                $message = ['type' => 'success', 'text' => 'Eintrag gespeichert.'];
            } else {
                $message = ['type' => 'warning', 'text' => 'Eintrag existiert bereits.'];
            }
        }

        // Meta-Daten aktualisieren
        if (isset($_POST['save_meta']) && isset($_POST['meta_title']) && is_array($_POST['meta_title'])) {
            foreach ($_POST['meta_title'] as $id => $title_value) {
                $desc_value = $_POST['meta_description'][$id] ?? '';
                $snippet_id = isset($_POST['snippet_id'][$id]) ? (int)$_POST['snippet_id'][$id] : null;
                $wpdb->update($table, [
                    'meta_title' => sanitize_text_field($title_value),
                    'meta_description' => sanitize_textarea_field($desc_value),
                    'snippet_id' => $snippet_id
                ], ['id' => (int) $id]);
            }
            $message = ['type' => 'success', 'text' => 'Meta-Daten aktualisiert.'];
        }

        // Sitemap aktualisieren
        if (isset($_POST['action']) && $_POST['action'] === 'lokale_seo_update_sitemap') {
            $ids = [];

            if (isset($_POST['write_single'])) {
                $ids[] = (int) $_POST['write_single'];
            } elseif (isset($_POST['write_bulk']) && !empty($_POST['selected'])) {
                $ids = array_map('intval', $_POST['selected']);
            }

            if (!empty($ids)) {
                $sitemap_path = dirname(WP_CONTENT_DIR) . '/lokale-sitemap.xml';
                $site_url = get_site_url();

                if (!file_exists($sitemap_path) || filesize($sitemap_path) === 0) {
                    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
                } else {
                    libxml_use_internal_errors(true);
                    $xml = simplexml_load_file($sitemap_path);
                    if ($xml === false) {
                        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
                    }
                }

                $added = 0;

                foreach ($ids as $id) {
                    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
                    if (!$row) continue;

                    $url = $site_url . '/' . $row->slug;
                    $exists = false;

                    foreach ($xml->url as $entry) {
                        if ((string)$entry->loc === $url) {
                            $exists = true;
                            break;
                        }
                    }

                    if (!$exists) {
                        $entry = $xml->addChild('url');
                        $entry->addChild('loc', $url);
                        $entry->addChild('lastmod', date('Y-m-d'));
                        $entry->addChild('changefreq', 'monthly');
                        $entry->addChild('priority', '0.8');
                        $added++;
                    }
                }

                if ($xml->asXML($sitemap_path)) {
                    $message = ['type' => 'success', 'text' => "$added URL(s) zur Sitemap hinzugefügt."];
                } else {
                    $message = ['type' => 'error', 'text' => "Fehler beim Schreiben der Sitemap."];
                }
            }
        }
    }

    $entries = $wpdb->get_results("SELECT id, title, slug, meta_title, meta_description, snippet_id FROM $table ORDER BY id DESC");
?>

    <div class="wrap">
        <h1>Lokale SEO Seiten verwalten</h1>
        <p><a href="<?= esc_url(site_url('/lokale-sitemap.xml')); ?>" target="_blank">Sitemap anzeigen</a></p>

        <?php if ($message): ?>
            <div class="notice notice-<?= esc_attr($message['type']); ?> is-dismissible">
                <p><?= esc_html($message['text']); ?></p>
            </div>
        <?php endif; ?>

        <div style="display: flex; flex-wrap: wrap; gap: 2em; align-items: flex-start; margin-bottom: 2em;">
            <div style="flex: 1 1 600px;">
                <h2>Eintrag anlegen</h2>
                <form method="post" style="display: flex; gap: 1em; align-items: center;">
                    <input type="text" name="seitentitel" placeholder="z. B. Badsanierung München" required style="max-width: 400px; width: 100%;">
                    <input type="submit" class="button button-primary" value="Eintrag speichern">
                </form>
            </div>

            <div style="flex: 1 1 300px;">
                <h2>CSV-Import</h2>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="csv_file" accept=".csv" required style="width: 100%;">
                    <input type="submit" class="button" value="Import starten" name="import_csv">
                </form>
            </div>
        </div>

        <h2 style="margin-top: 2em;">Gespeicherte Einträge</h2>
        <form method="post">
            <input type="hidden" name="action" value="lokale_seo_update_sitemap">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th>Titel</th>
                        <th>Slug</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $in_sitemap = false; // Vor der foreach initialisieren
                    foreach ($entries as $entry):
                        $sitemap_path = dirname(WP_CONTENT_DIR) . '/lokale-sitemap.xml';
                        $url = get_site_url() . '/' . $entry->slug;
                        $in_sitemap = false;

                        if (file_exists($sitemap_path)) {
                            libxml_use_internal_errors(true);
                            $xml = simplexml_load_file($sitemap_path);
                            if ($xml !== false) {
                                foreach ($xml->url as $xml_url) {
                                    if ((string)$xml_url->loc === $url) {
                                        $in_sitemap = true;
                                        break;
                                    }
                                }
                            }
                        }
                    ?>
                        <tr class="entry-row">
                            <td><input type="checkbox" name="selected[]" value="<?= esc_attr($entry->id); ?>"></td>
                            <td colspan="3">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?= esc_html($entry->title); ?><?= $in_sitemap ? ' <span style="color:green;">✓</span>' : ''; ?></strong>
                                    </div>
                                    <div>
                                        <?php if ($in_sitemap): ?>
                                            <a href="<?= esc_url(home_url('/' . $entry->slug)); ?>" target="_blank" class="button button-secondary" style="margin-right: 0.5em;">Seite ansehen</a>
                                        <?php endif; ?>
                                        <button type="button" class="toggle-details button small">Details</button>
                                    </div>
                                </div>
                                <div class="entry-details" style="display: none; margin-top: 1em;">
                                    <div style="font-size: 0.9em; color: #666; margin-bottom: 1em;">Slug: <code><?= esc_html($entry->slug); ?></code></div>

                                    <div style="display: flex; gap: 1em; flex-wrap: wrap;">
                                        <div style="flex: 1 1 300px;">
                                            <label><strong>Meta Title:</strong><br>
                                                <input type="text" name="meta_title[<?= $entry->id; ?>]" value="<?= esc_attr($entry->meta_title); ?>" style="width:100%;">
                                                <small><?= mb_strlen($entry->meta_title); ?> Zeichen</small>
                                            </label>
                                        </div>

                                        <div style="flex: 1 1 300px;">
                                            <label><strong>Meta Description:</strong><br>
                                                <textarea name="meta_description[<?= $entry->id; ?>]" rows="3" style="width:100%;"><?= esc_textarea($entry->meta_description); ?></textarea>
                                                <small><?= mb_strlen($entry->meta_description); ?> Zeichen</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div style="margin-top: 1em;">
                                        <label><strong>Textbaustein wählen:</strong><br>
                                            <select name="snippet_id[<?= $entry->id; ?>]" style="width:100%; max-width: 300px;">
                                                <option value="">-- kein Baustein --</option>
                                                <?php foreach ($snippets as $snippet): ?>
                                                    <option value="<?= $snippet->id; ?>" <?= $entry->snippet_id == $snippet->id ? 'selected' : ''; ?>>
                                                        Baustein <?= $snippet->id; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>

                                    <div style="margin-top: 1em;">
                                        <button type="submit" class="button button-primary" name="save_meta_single" value="<?= esc_attr($entry->id); ?>">Meta speichern</button>
                                        <button type="submit" class="button button-secondary" name="write_single" value="<?= esc_attr($entry->id); ?>" style="margin-left: 0.5em;">Zur Sitemap</button>
                                        <?php if ($in_sitemap): ?>
                                            <div style="margin-top: 0.5em;">
                                                <a href="<?= esc_url(home_url('/' . $entry->slug)); ?>" target="_blank" class="button button-secondary">Seite ansehen</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($entries)): ?>
                <div style="margin-top: 1em; display: flex; flex-wrap: wrap; gap: 1em;">
                    <input type="submit" name="write_bulk" class="button button-secondary" value="Ausgewählte zur Sitemap hinzufügen">
                    <input type="submit" name="delete_selected" class="button button-danger" value="Ausgewählte löschen" onclick="return confirm('Ausgewählte Einträge wirklich löschen?');">
                    <input type="submit" name="delete_all" class="button" style="background:#800000;color:#fff;" value="Alle Einträge & Sitemap löschen" onclick="return confirm('Wirklich alles löschen?');">
                </div>
            <?php endif; ?>
        </form>
    </div>

    <script>
        function toggleAll(master) {
            document.querySelectorAll('input[name="selected[]"]').forEach(el => el.checked = master.checked);
        }
        document.querySelectorAll('input[name^="meta_title"], textarea[name^="meta_description"]').forEach(function(el) {
            el.addEventListener('input', function() {
                const counter = this.nextElementSibling;
                if (counter && counter.tagName.toLowerCase() === 'small') {
                    counter.textContent = this.value.length + ' Zeichen';
                }
            });
        });
        document.querySelectorAll('.toggle-details').forEach(btn => {
            btn.addEventListener('click', function() {
                const container = this.closest('td').querySelector('.entry-details');
                container.style.display = container.style.display === 'none' ? 'block' : 'none';
            });
        });
    </script>
<?php
}

// Öffentliche Seite für jeden Eintrag darstellen
add_action('init', function () {
    add_rewrite_rule('^([^/]+)/?$', 'index.php?lokal_seo_slug=$matches[1]', 'top');
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'lokal_seo_slug';
    return $vars;
});


add_filter('template_include', function ($template) {
    $slug = get_query_var('lokal_seo_slug');
    if (!$slug) return $template;

    global $wpdb;
    $table = $wpdb->prefix . 'lokale_seo_pages';
    $entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s", $slug));
    if (!$entry) return get_404_template();

    // Snippet einfügen
    $snippet = '';
    if (!empty($entry->snippet_id)) {
        $snippet = $wpdb->get_var($wpdb->prepare(
            "SELECT content FROM {$wpdb->prefix}lokale_seo_snippets WHERE id = %d",
            $entry->snippet_id
        ));
    }

    $GLOBALS['lokal_seo_entry'] = $entry;
    $GLOBALS['lokal_seo_content'] = $snippet
        ? str_replace('@@keyword@@', esc_html($entry->title), $snippet)
        : '<p>Kein Textbaustein zugewiesen.</p>';

    if (function_exists('view')) {
        echo view('lokale-seo', [
            'entry' => $entry,
            'content' => apply_filters('the_content', $GLOBALS['lokal_seo_content']),
        ])->render();
        exit;
    }

    return $template;
});
