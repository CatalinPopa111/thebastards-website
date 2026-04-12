<?php
// Determine the active tab or set a default
$default_tab = null; // Default tab when no tab parameter is present
$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
?>
<div class="p99-manager">
  <div class="p99-header">
    <div class="p99-logo"></div>
    <div class="p99-title"><h2>99Plugs Update Manager</h2></div>
    <div class="p99-link"><a href="https://99plugs.com" target="_blank">Visit 99Plugs</a></div>
  </div>   
  <nav class="nav-tab-wrapper">
    <a href="?page=p99-manager" class="nav-tab <?php echo $tab === null ? 'nav-tab-active' : ''; ?>">Update Keys</a>
    <a href="?page=p99-manager&tab=plugins" class="nav-tab <?php echo $tab === 'plugins' ? 'nav-tab-active' : ''; ?>">Plugins</a>
    <a href="?page=p99-manager&tab=themes" class="nav-tab <?php echo $tab === 'themes' ? 'nav-tab-active' : ''; ?>">Themes</a>
    <a href="?page=p99-manager&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
  </nav>
  <div class="tab-content">
    <?php 
    // Switch between tabs and include the respective content
    switch ($tab) {
      case 'plugins':
        include(P99_PLUGIN_PATH . '/views/plugins.php'); // Include the plugins view
        break;
      case 'themes':
        include(P99_PLUGIN_PATH . '/views/themes.php'); // Include the themes view
        break;
      case 'settings':
        include(P99_PLUGIN_PATH . '/views/settings.php'); // Include the settings view
        break;
      default:
        include(P99_PLUGIN_PATH . '/views/keys.php'); // Default view for update keys
        break;
    }
    ?>
  </div>
</div>
