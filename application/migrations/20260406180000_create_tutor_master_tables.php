<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_tutor_master_tables extends CI_Migration
{
    public function up()
    {
        $this->db->query("SET FOREIGN_KEY_CHECKS=0;");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS tutor_categories (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(180) DEFAULT NULL,
                status TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_tutor_categories_name (name),
                KEY idx_tutor_categories_status_order (status, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS tutor_classes (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                category_id INT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(180) DEFAULT NULL,
                status TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_tutor_classes_category_name (category_id, name),
                KEY idx_tutor_classes_category_status_order (category_id, status, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS tutor_subject_master (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                class_id INT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(180) DEFAULT NULL,
                status TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_tutor_subject_class_name (class_id, name),
                KEY idx_tutor_subject_class_status_order (class_id, status, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Keep the existing tutor_subjects mapping table, but make it explicit.
        if (!$this->db->field_exists('category_id', 'tutor_subjects')) {
            $this->db->query("ALTER TABLE tutor_subjects ADD COLUMN category_id INT UNSIGNED NULL AFTER tutor_profile_id");
        }
        if (!$this->db->field_exists('class_id', 'tutor_subjects')) {
            $this->db->query("ALTER TABLE tutor_subjects ADD COLUMN class_id INT UNSIGNED NULL AFTER category_id");
        }
        if (!$this->db->field_exists('subject_id', 'tutor_subjects')) {
            $this->db->query("ALTER TABLE tutor_subjects ADD COLUMN subject_id INT UNSIGNED NULL AFTER class_id");
        }

        @ $this->db->query("CREATE INDEX idx_tutor_subjects_category ON tutor_subjects(category_id)");
        @ $this->db->query("CREATE INDEX idx_tutor_subjects_class ON tutor_subjects(class_id)");
        @ $this->db->query("CREATE INDEX idx_tutor_subjects_subject ON tutor_subjects(subject_id)");

        // Seed only when master tables are empty.
        $has_categories = (int) $this->db->count_all('tutor_categories');
        if ($has_categories === 0) {
            $this->seed_defaults();
        }

        $this->db->query("SET FOREIGN_KEY_CHECKS=1;");

        @ $this->db->query("ALTER TABLE tutor_classes ADD CONSTRAINT fk_tutor_classes_category FOREIGN KEY (category_id) REFERENCES tutor_categories(id) ON DELETE CASCADE ON UPDATE CASCADE");
        @ $this->db->query("ALTER TABLE tutor_subject_master ADD CONSTRAINT fk_tutor_subject_master_class FOREIGN KEY (class_id) REFERENCES tutor_classes(id) ON DELETE CASCADE ON UPDATE CASCADE");
        @ $this->db->query("ALTER TABLE tutor_subjects ADD CONSTRAINT fk_tutor_subjects_category_master FOREIGN KEY (category_id) REFERENCES tutor_categories(id) ON DELETE SET NULL ON UPDATE CASCADE");
        @ $this->db->query("ALTER TABLE tutor_subjects ADD CONSTRAINT fk_tutor_subjects_class_master FOREIGN KEY (class_id) REFERENCES tutor_classes(id) ON DELETE SET NULL ON UPDATE CASCADE");
        @ $this->db->query("ALTER TABLE tutor_subjects ADD CONSTRAINT fk_tutor_subjects_subject_master FOREIGN KEY (subject_id) REFERENCES tutor_subject_master(id) ON DELETE CASCADE ON UPDATE CASCADE");
    }

    public function down()
    {
        // Keep mapping data safe; do not drop the existing tutor_subjects table automatically.
        $this->db->query("SET FOREIGN_KEY_CHECKS=0;");
        $this->db->query("DROP TABLE IF EXISTS tutor_subject_master;");
        $this->db->query("DROP TABLE IF EXISTS tutor_classes;");
        $this->db->query("DROP TABLE IF EXISTS tutor_categories;");
        $this->db->query("SET FOREIGN_KEY_CHECKS=1;");
    }

    private function seed_defaults()
    {
        $categories = [
            ['name' => 'School Courses', 'slug' => 'school-courses', 'sort_order' => 1],
            ['name' => 'College Courses', 'slug' => 'college-courses', 'sort_order' => 2],
            ['name' => 'IT & Professional Courses', 'slug' => 'it-professional-courses', 'sort_order' => 3],
        ];
        foreach ($categories as $row) {
            $this->db->insert('tutor_categories', $row);
        }

        $cat_map = [];
        foreach ($this->db->get('tutor_categories')->result_array() as $r) {
            $cat_map[$r['name']] = (int)$r['id'];
        }

        $classes = [];
        for ($i = 1; $i <= 12; $i++) {
            $classes[] = ['category_id' => $cat_map['School Courses'], 'name' => 'Class ' . $i, 'slug' => 'class-' . $i, 'sort_order' => $i];
        }
        $classes = array_merge($classes, [
            ['category_id' => $cat_map['College Courses'], 'name' => 'B.Tech', 'slug' => 'btech', 'sort_order' => 1],
            ['category_id' => $cat_map['College Courses'], 'name' => 'IT', 'slug' => 'it', 'sort_order' => 2],
            ['category_id' => $cat_map['IT & Professional Courses'], 'name' => 'Programming Languages', 'slug' => 'programming-languages', 'sort_order' => 1],
            ['category_id' => $cat_map['IT & Professional Courses'], 'name' => 'Cloud Computing', 'slug' => 'cloud-computing', 'sort_order' => 2],
            ['category_id' => $cat_map['IT & Professional Courses'], 'name' => 'DevOps', 'slug' => 'devops', 'sort_order' => 3],
            ['category_id' => $cat_map['IT & Professional Courses'], 'name' => 'Data Science & AI', 'slug' => 'data-science-ai', 'sort_order' => 4],
        ]);
        foreach ($classes as $row) {
            $this->db->insert('tutor_classes', $row);
        }

        $class_map = [];
        foreach ($this->db->get('tutor_classes')->result_array() as $r) {
            $class_map[$r['name']] = (int)$r['id'];
        }

        $subjects_by_class = [
            'Class 1' => ['Maths','English','EVS','Hindi'],
            'Class 2' => ['Maths','English','EVS','Hindi'],
            'Class 3' => ['Maths','English','EVS','Hindi'],
            'Class 4' => ['Maths','English','EVS','Hindi'],
            'Class 5' => ['Maths','English','EVS','Hindi'],
            'Class 6' => ['Maths','Science','English','Hindi','Social Science'],
            'Class 7' => ['Maths','Science','English','Hindi','Social Science'],
            'Class 8' => ['Maths','Science','English','Hindi','Social Science'],
            'Class 9' => ['Maths','Science','Physics','Chemistry','Biology','English','Social Science'],
            'Class 10' => ['Maths','Science','Physics','Chemistry','Biology','English','Social Science'],
            'Class 11' => ['Maths','Physics','Chemistry','Biology','Computer Science','English'],
            'Class 12' => ['Maths','Physics','Chemistry','Biology','Computer Science','English'],
            'B.Tech' => ['Programming','Data Structures','Algorithms','DBMS','Operating Systems','Computer Networks','Artificial Intelligence','Machine Learning','Data Science','Cloud Computing'],
            'IT' => ['Java','Python','C++','JavaScript','Web Development','AWS','GCP','DevOps','Data Science','Artificial Intelligence'],
            'Programming Languages' => ['Java','Python','C++','JavaScript','PHP','Node.js','React'],
            'Cloud Computing' => ['AWS','GCP','Azure','Cloud Architecture','Cloud Security'],
            'DevOps' => ['Linux','Git','Jenkins','Docker','Kubernetes','CI/CD','Terraform','Monitoring'],
            'Data Science & AI' => ['Statistics','Python for Data Science','Machine Learning','Deep Learning','GenAI','Prompt Engineering','MLOps'],
        ];

        foreach ($subjects_by_class as $class_name => $subjects) {
            if (empty($class_map[$class_name])) {
                continue;
            }
            $sort = 1;
            foreach ($subjects as $subject) {
                $this->db->insert('tutor_subject_master', [
                    'class_id' => $class_map[$class_name],
                    'name' => $subject,
                    'slug' => $this->slugify($subject),
                    'sort_order' => $sort++,
                ]);
            }
        }
    }

    private function slugify($value)
    {
        $value = strtolower(trim((string)$value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
        return trim($value, '-');
    }
}
