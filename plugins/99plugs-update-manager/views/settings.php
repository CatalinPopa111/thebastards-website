<div class="wrap">
    <h2>Settings</h2>
    <form method="post" action="options.php">
        <?php settings_fields( 'p99_settings' ); ?>
        <?php do_settings_sections( 'p99_settings' ); ?>
        <table class="p99-settings">
            <tr>
                <td><h3>Plugins and Themes Tables</h3></td>
            </tr>
            <tr>
                <td>Plugins and themes to show</td>
                <td>
                    <select name="p99_items_to_show" id="p99_items_to_show">
                        <option value="All" <?php selected( get_option( 'p99_items_to_show' ), 'All' ); ?>>All</option>
                        <option value="99Plugs" <?php selected( get_option( 'p99_items_to_show' ), '99Plugs' ); ?>>99Plugs Only</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><h3>Backups</h3></td>
            </tr>
            <tr>
                <td>Enable backups</td>
                <td><input type="checkbox" name="p99_enable_backups" id="p99_enable_backups" <?php checked( get_option( 'p99_enable_backups' ), 'on' ); ?>></td>
            </tr>
            <tr>
                <td>Plugins and themes to backup</td>
                <td>
                    <select name="p99_items_to_backup" id="p99_items_to_backup">
                        <option value="All" <?php selected( get_option( 'p99_items_to_backup' ), 'All' ); ?>>All</option>
                        <option value="99Plugs" <?php selected( get_option( 'p99_items_to_backup' ), '99Plugs' ); ?>>99Plugs Only</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Number of backups to keep</td>
                <td>
                    <select name="p99_backups_to_keep" id="p99_backups_to_keep">
                        <option value="1" <?php selected( get_option( 'p99_backups_to_keep' ), '1' ); ?>>1</option>
                        <option value="2" <?php selected( get_option( 'p99_backups_to_keep' ), '2' ); ?>>2</option>
                        <option value="3" <?php selected( get_option( 'p99_backups_to_keep' ), '3' ); ?>>3</option>
                        <option value="4" <?php selected( get_option( 'p99_backups_to_keep' ), '4' ); ?>>4</option>
                        <option value="5" <?php selected( get_option( 'p99_backups_to_keep' ), '5' ); ?>>5</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><?php submit_button( 'SAVE SETTINGS' ); ?></td>
            </tr>
        </table>
    </form>
</div>

