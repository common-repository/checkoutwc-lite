import { cfwDomReady }        from '../_functions';
import FontSelector           from './components/FontSelector';
import ImagePicker            from './components/ImagePicker';
import RichEditor             from './components/RichEditor';
import SettingsExporterButton from './components/SettingsExporterButton';
import SettingsImporterButton from './components/SettingsImporterButton';

cfwDomReady( () => {
    /**
     * Unload Handler
     */
    let changed = false;
    const beforeUnloadHandler = ( event ) => {
        if ( changed ) {
            event.preventDefault();
            event.returnValue = ( <any>window ).objectiv_cfw_admin.i18n_nav_warning;
        }
    };

    const fieldChangedHandler = () => {
        jQuery( '#cfw_admin_header_save_button' ).removeClass( 'cfw-save-inactive' );

        changed = true;
    };

    jQuery( document.body ).on( 'input keydown', '.cfw-tw', fieldChangedHandler  );
    jQuery( document.body ).on( 'cfw_admin_field_changed', fieldChangedHandler  );

    window.addEventListener( 'beforeunload', beforeUnloadHandler );

    jQuery( document.body ).on( 'click', '#cfw_admin_page_submit, #submit, #publish', () => {
        window.removeEventListener( 'beforeunload', beforeUnloadHandler );
    } );

    /**
     * Code Editors
     */
    // Header Scripts
    new RichEditor( '#_cfwlite__settingheader_scripts_checkoutstring' );

    // Footer Scripts
    new RichEditor( '#_cfwlite__settingfooter_scripts_checkoutstring' );

    // Custom CSS
    new RichEditor( '#_cfwlite__settingcustom_css_defaultstring', 'css' );

    /**
     * Color Pickers
     */
    jQuery( '.cfw-admin-color-picker' ).wpColorPicker( {
        change: fieldChangedHandler,
    } );

    /**
     * Font Selectors
     */
    new FontSelector( '#cfw-body-font-selector' );
    new FontSelector( '#cfw-heading-font-selector' );

    /**
     * Settings Export / Import
     */
    new SettingsExporterButton( '#export_settings_button' );
    new SettingsImporterButton( '#import_settings_button' );
    /**
     * Image Pickers
     */
    new ImagePicker( '.cfw-admin-image-picker-button' );

    // Enable Select2
    jQuery( document.body ).trigger( 'wc-enhanced-select-init' );

    jQuery( document.body ).on( 'click', '#cfw_admin_header_save_button', ( e ) => {
        e.preventDefault();
        jQuery( '#cfw_admin_page_submit' ).trigger( 'click' );
    } );
} );
