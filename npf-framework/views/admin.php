<div class="wrap npf-wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <?php if ( 'options-general.php' != $this->parent_page ): ?>
      <?php settings_errors(); ?>
    <?php endif ?>
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                    <div id="npf-tab-container" class="tab-container">

                       <h2 class="nav-tab-wrapper">

                        <?php foreach ($this->base_args['tabs'] as $tab_key => $tab_value) : ?>

                         <span id="<?php echo 'tab-' . esc_attr( $this->base_args['menu_slug'] . '-' . $tab_key ); ?>"><a href="#npf-<?php echo $this->base_args['menu_slug'].'-'.$tab_value['id']; ?>" class="nav-tab"><?php echo $tab_value['title']; ?></a></span>

                        <?php endforeach ?>

                       </h2>

                       <form action="options.php" method="post" class="postbox">
                       <?php

                       settings_fields($this->base_args['option_slug'].'-group');

                       foreach ($this->base_args['tabs'] as $tab_key => $tab) {

                         echo '<div id="npf-'.$this->base_args['menu_slug'].'-'.$tab['id'].'" class="single-tab-content">';
                         do_settings_sections($tab['id'].'-'.$this->base_args['menu_slug']);
                         echo '</div>';

                       }
                       submit_button(__('Save Changes', 'admin-customizer'));

                        ?>
                      </form>

                     </div> <!-- #npf-tab-container -->
            </div><!-- #post-body-content -->

            <div id="postbox-container-1" class="postbox-container">
                <?php
                    do_action( 'npf_sidebar_' . $this->base_args['menu_slug'] );
                ?>
            </div> <!-- #postbox-container-1 .postbox-container -->
        </div><!-- #post-body -->
    </div> <!-- #poststuff -->
</div> <!-- .wrap -->
