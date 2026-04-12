<div class="p99-keys">
  <div class="instruct">
    <p>To receive updates for your plugins and themes from 99Plugs, simply activate your update keys below and you're good to go!</p>
    <p>Your update keys can be found in your <a href="https://99plugs.com/dashboard/" target="_blank">99Plugs Dashboard</a> under Manage Update Keys.</p>
    <p>For additional help, check out our <a href="https://99plugs.com/99plugs-update-manager-step-by-step-guide/" target="_blank">99Plugs Update Manager Step-by-Step Guide</a>.</p>
  </div>
  <div class="p99-form">
    <div class="wrapper">
      <form action="<?php echo admin_url('admin-ajax.php'); ?>" method="post" id="p99-form">
        <input type="hidden" name="action" value="p99_save_data" />
        <?php
          $data = get_option('apikeys', [['keyname' => '', 'keyvalue' => '', 'keystatus' => '']]);
        ?>
        <div class="rows-wrapper">
          <?php foreach ($data as $i => $row) : ?>
            <div class="row" data-key="<?php echo $i; ?>">
              <input type="hidden" name="data[<?php echo $i; ?>][keystatus]" value="<?php echo $row['keystatus']; ?>" />
              <div class="form-group">
                <input type="text" name="data[<?php echo $i; ?>][keyname]" placeholder="Item Name" value="<?php echo $row['keyname']; ?>" />
              </div>
              <div class="form-group">
                <input type="text" class="key_input" name="data[<?php echo $i; ?>][keyvalue]" placeholder="Update Key" value="<?php echo !empty($row['keyvalue']) ? '********************' : ''; ?>" />
              </div>
              <div class="p99-action">
                <div class="response_status no-clone"><?php echo !empty($row['keystatus']) ? $row['keystatus'] : 'Inactive'; ?></div>
                <div class="prime-actions no-clone">
                  <?php if (!empty($row['keyname']) && !empty($row['keyvalue'])): ?>
                    <?php if (in_array($row['keystatus'], ['', 'Inactive', 'Expired'])): ?>
                      <button data-action="plugs99_activate" type="button" data-name="<?php echo $row['keyname']; ?>" class="button dynamic-actions">ACTIVATE</button>
                    <?php else: ?>
                      <button data-action="plugs99_check" type="button" data-name="<?php echo $row['keyname']; ?>" class="button dynamic-actions">CHECK</button>
                      <button type="button" data-action="plugs99_deactivate" data-name="<?php echo $row['keyname']; ?>" class="button dynamic-actions">DEACTIVATE</button>
                    <?php endif; ?>
                    <?php if ($row['keystatus'] == 'Expired'): ?>
                      <button type="button" data-action="plugs99_renew" data-name="<?php echo $row['keyname']; ?>" class="button dynamic-actions renew-button">RENEW</button>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
                <button type="button" class="button action remove-btn"></button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button class="p99-append-row button add-btn" type="button">ADD MORE</button>
        <div class="action-row">
          <button id="p99-save-form" type="submit" class="p99-save-form button button-primary">SAVE UPDATE KEYS</button>
          <div class="status-message"></div>
          <p>Always save changes before activating or deactivating keys.</p>
        </div>
      </form>
    </div>
  </div>
</div>
