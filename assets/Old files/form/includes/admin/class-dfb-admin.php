<?php
/**
 * Admin class for the Form Builder
 */

class DFB_Admin {
    
    public function __construct() {
        // Add menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Ajax actions
        add_action('wp_ajax_dfb_save_form', array($this, 'ajax_save_form'));
        add_action('wp_ajax_dfb_save_field', array($this, 'ajax_save_field'));
        add_action('wp_ajax_dfb_delete_field', array($this, 'ajax_delete_field'));
        add_action('wp_ajax_dfb_delete_form', array($this, 'ajax_delete_form'));
        add_action('wp_ajax_dfb_sort_fields', array($this, 'ajax_sort_fields'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Form Builder',
            'Form Builder',
            'manage_options',
            'dynamic-form-builder',
            array($this, 'display_forms_page'),
            'dashicons-feedback',
            30
        );
        
        add_submenu_page(
            'dynamic-form-builder',
            'All Forms',
            'All Forms',
            'manage_options',
            'dynamic-form-builder',
            array($this, 'display_forms_page')
        );
        
        add_submenu_page(
            'dynamic-form-builder',
            'Add New Form',
            'Add New Form',
            'manage_options',
            'dfb-new-form',
            array($this, 'display_form_builder')
        );
        
        add_submenu_page(
            null,
            'Edit Form',
            'Edit Form',
            'manage_options',
            'dfb-edit-form',
            array($this, 'display_form_builder')
        );
        
        add_submenu_page(
            null,
            'Form Entries',
            'Form Entries',
            'manage_options',
            'dfb-form-entries',
            array($this, 'display_form_entries')
        );
    }
    
    /**
     * Register scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Only enqueue on plugin pages
        if (strpos($hook, 'dynamic-form-builder') === false && 
            strpos($hook, 'dfb-new-form') === false && 
            strpos($h