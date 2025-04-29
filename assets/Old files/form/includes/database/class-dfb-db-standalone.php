<?php
/**
 * Database class for standalone form builder
 */

class DFB_DB_Standalone {
    
    private $forms_table = 'dfb_forms';
    private $form_fields_table = 'dfb_form_fields';
    private $form_entries_table = 'dfb_form_entries';
    private $pdo;
    
    public function __construct() {
        try {
            // Load configuration
            $config_file = dirname(dirname(dirname(__FILE__))) . '/config.php';
            $config = include $config_file;
            
            if (!is_array($config)) {
                throw new Exception("Invalid configuration format. Expected array.");
            }
            
            // Create PDO connection
            $this->pdo = new PDO(
                "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
                $config['db_user'],
                $config['db_password']
            );
            
            // Set error mode
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch (Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Check if tables exist
     */
    public function tables_exist() {
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE :table");
            $stmt->execute(['table' => $this->forms_table]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        $forms_table = "CREATE TABLE IF NOT EXISTS {$this->forms_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            email_recipients text,
            webhook_url varchar(255),
            success_message text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $form_fields_table = "CREATE TABLE IF NOT EXISTS {$this->form_fields_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            form_id int(11) NOT NULL,
            field_type varchar(50) NOT NULL,
            label varchar(255) NOT NULL,
            placeholder text,
            options text,
            required tinyint(1) DEFAULT 0,
            field_order int(11) DEFAULT 0,
            field_class varchar(255),
            field_id varchar(255),
            validation_rules text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY form_id (form_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $form_entries_table = "CREATE TABLE IF NOT EXISTS {$this->form_entries_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            form_id int(11) NOT NULL,
            entry_data longtext NOT NULL,
            user_ip varchar(100),
            user_agent text,
            submitted_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY form_id (form_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        try {
            $this->pdo->exec($forms_table);
            $this->pdo->exec($form_fields_table);
            $this->pdo->exec($form_entries_table);
            return true;
        } catch (PDOException $e) {
            die("Table creation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get form by ID
     */
    public function get_form($form_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->forms_table} WHERE id = :id");
            $stmt->execute(['id' => $form_id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all forms
     */
    public function get_forms() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM {$this->forms_table} ORDER BY id DESC");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get form fields
     */
    public function get_form_fields($form_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->form_fields_table} WHERE form_id = :form_id ORDER BY field_order ASC");
            $stmt->execute(['form_id' => $form_id]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Create a new form
     */
    public function create_form($form_data) {
        try {
            $now = date('Y-m-d H:i:s');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->forms_table} 
                (title, description, email_recipients, webhook_url, success_message, created_at, updated_at) 
                VALUES 
                (:title, :description, :email_recipients, :webhook_url, :success_message, :created_at, :updated_at)
            ");
            
            $stmt->execute([
                'title' => $form_data['title'],
                'description' => isset($form_data['description']) ? $form_data['description'] : '',
                'email_recipients' => isset($form_data['email_recipients']) ? $form_data['email_recipients'] : '',
                'webhook_url' => isset($form_data['webhook_url']) ? $form_data['webhook_url'] : '',
                'success_message' => isset($form_data['success_message']) ? $form_data['success_message'] : 'Form submitted successfully!',
                'created_at' => $now,
                'updated_at' => $now
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update form
     */
    public function update_form($form_id, $form_data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->forms_table} SET
                title = :title,
                description = :description,
                email_recipients = :email_recipients,
                webhook_url = :webhook_url,
                success_message = :success_message,
                updated_at = :updated_at
                WHERE id = :id
            ");
            
            $stmt->execute([
                'title' => $form_data['title'],
                'description' => isset($form_data['description']) ? $form_data['description'] : '',
                'email_recipients' => isset($form_data['email_recipients']) ? $form_data['email_recipients'] : '',
                'webhook_url' => isset($form_data['webhook_url']) ? $form_data['webhook_url'] : '',
                'success_message' => isset($form_data['success_message']) ? $form_data['success_message'] : 'Form submitted successfully!',
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $form_id
            ]);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Delete form
     */
    public function delete_form($form_id) {
        try {
            // Delete form fields
            $stmt = $this->pdo->prepare("DELETE FROM {$this->form_fields_table} WHERE form_id = :form_id");
            $stmt->execute(['form_id' => $form_id]);
            
            // Delete form entries
            $stmt = $this->pdo->prepare("DELETE FROM {$this->form_entries_table} WHERE form_id = :form_id");
            $stmt->execute(['form_id' => $form_id]);
            
            // Delete form
            $stmt = $this->pdo->prepare("DELETE FROM {$this->forms_table} WHERE id = :id");
            $stmt->execute(['id' => $form_id]);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Add form field
     */
    public function add_form_field($field_data) {
        try {
            $now = date('Y-m-d H:i:s');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->form_fields_table}
                (form_id, field_type, label, placeholder, options, required, field_order, field_class, field_id, validation_rules, created_at, updated_at)
                VALUES
                (:form_id, :field_type, :label, :placeholder, :options, :required, :field_order, :field_class, :field_id, :validation_rules, :created_at, :updated_at)
            ");
            
            $stmt->execute([
                'form_id' => $field_data['form_id'],
                'field_type' => $field_data['field_type'],
                'label' => $field_data['label'],
                'placeholder' => isset($field_data['placeholder']) ? $field_data['placeholder'] : '',
                'options' => isset($field_data['options']) ? $field_data['options'] : '',
                'required' => isset($field_data['required']) ? $field_data['required'] : 0,
                'field_order' => isset($field_data['field_order']) ? $field_data['field_order'] : 0,
                'field_class' => isset($field_data['field_class']) ? $field_data['field_class'] : '',
                'field_id' => isset($field_data['field_id']) ? $field_data['field_id'] : '',
                'validation_rules' => isset($field_data['validation_rules']) ? $field_data['validation_rules'] : '',
                'created_at' => $now,
                'updated_at' => $now
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update form field
     */
    public function update_form_field($field_id, $field_data) {
        try {
            $updates = [];
            $params = ['id' => $field_id];
            
            if (isset($field_data['field_type']) && !empty($field_data['field_type'])) {
                $updates[] = "field_type = :field_type";
                $params['field_type'] = $field_data['field_type'];
            }
            
            if (isset($field_data['label']) && !empty($field_data['label'])) {
                $updates[] = "label = :label";
                $params['label'] = $field_data['label'];
            }
            
            if (isset($field_data['placeholder'])) {
                $updates[] = "placeholder = :placeholder";
                $params['placeholder'] = $field_data['placeholder'];
            }
            
            if (isset($field_data['options'])) {
                $updates[] = "options = :options";
                $params['options'] = $field_data['options'];
            }
            
            if (isset($field_data['required'])) {
                $updates[] = "required = :required";
                $params['required'] = $field_data['required'];
            }
            
            if (isset($field_data['field_order'])) {
                $updates[] = "field_order = :field_order";
                $params['field_order'] = $field_data['field_order'];
            }
            
            if (isset($field_data['field_class'])) {
                $updates[] = "field_class = :field_class";
                $params['field_class'] = $field_data['field_class'];
            }
            
            if (isset($field_data['field_id'])) {
                $updates[] = "field_id = :field_id";
                $params['field_id'] = $field_data['field_id'];
            }
            
            if (isset($field_data['validation_rules'])) {
                $updates[] = "validation_rules = :validation_rules";
                $params['validation_rules'] = $field_data['validation_rules'];
            }
            
            $updates[] = "updated_at = :updated_at";
            $params['updated_at'] = date('Y-m-d H:i:s');
            
            if (empty($updates)) {
                return true;
            }
            
            $sql = "UPDATE {$this->form_fields_table} SET " . implode(", ", $updates) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Delete form field
     */
    public function delete_form_field($field_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->form_fields_table} WHERE id = :id");
            $stmt->execute(['id' => $field_id]);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Store form entry
     */
    public function store_entry($form_id, $entry_data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->form_entries_table} 
                (form_id, entry_data, user_ip, user_agent, submitted_at) 
                VALUES (:form_id, :entry_data, :user_ip, :user_agent, :submitted_at)
            ");
            
            $stmt->execute([
                'form_id' => $form_id,
                'entry_data' => json_encode($entry_data),
                'user_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown',
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown',
                'submitted_at' => date('Y-m-d H:i:s')
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get form entries
     */
    public function get_form_entries($form_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->form_entries_table} WHERE form_id = :form_id ORDER BY submitted_at DESC");
            $stmt->execute(['form_id' => $form_id]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get entry by ID
     */
    public function get_entry($entry_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->form_entries_table} WHERE id = :id");
            $stmt->execute(['id' => $entry_id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return false;
        }
    }
}