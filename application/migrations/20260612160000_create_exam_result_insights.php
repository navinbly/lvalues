<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_exam_result_insights extends CI_Migration
{
    public function up()
    {
        $this->add_column('content_exam_attempts', 'accuracy_percentage', "DECIMAL(10,2) NULL AFTER percentage");
        $this->add_column('content_exam_attempts', 'performance_delta', "DECIMAL(10,2) NULL AFTER accuracy_percentage");

        $this->db->query("CREATE TABLE IF NOT EXISTS content_exam_attempt_insights (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            attempt_id INT UNSIGNED NOT NULL,
            exam_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL DEFAULT 0,
            dimension_type VARCHAR(30) NOT NULL,
            dimension_key VARCHAR(190) NOT NULL,
            dimension_label VARCHAR(190) NOT NULL,
            total_questions INT UNSIGNED NOT NULL DEFAULT 0,
            attempted_questions INT UNSIGNED NOT NULL DEFAULT 0,
            correct_answers INT UNSIGNED NOT NULL DEFAULT 0,
            incorrect_answers INT UNSIGNED NOT NULL DEFAULT 0,
            skipped_questions INT UNSIGNED NOT NULL DEFAULT 0,
            marks_obtained DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_marks DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            accuracy_percentage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            score_percentage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_attempt_insight_dimension (attempt_id, dimension_type, dimension_key),
            KEY idx_insight_exam_dimension (exam_id, dimension_type, dimension_key),
            KEY idx_insight_user_dimension (user_id, dimension_type, dimension_key),
            CONSTRAINT fk_attempt_insight_attempt FOREIGN KEY (attempt_id)
                REFERENCES content_exam_attempts(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->add_index('content_exam_attempts', 'idx_attempt_exam_score', array('exam_id','submitted_at','percentage'));
        $this->backfill_legacy_results();
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS content_exam_attempt_insights");
    }

    private function add_column($table, $column, $definition)
    {
        if ($this->db->table_exists($table) && !$this->db->field_exists($column, $table)) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function add_index($table, $index, $columns)
    {
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=? LIMIT 1',
            array($table, $index)
        )->num_rows() > 0;
        if (!$exists) {
            $this->db->query("CREATE INDEX {$index} ON {$table}(`" . implode('`,`', $columns) . "`)");
        }
    }

    private function backfill_legacy_results()
    {
        $this->db->query("UPDATE content_exam_attempts a
            JOIN content_exams e ON e.id=a.exam_id
            JOIN (
                SELECT aa.attempt_id,COUNT(*) total_questions,
                    SUM(CASE WHEN aa.selected_option_ids IS NOT NULL AND aa.selected_option_ids<>'[]' THEN 1 ELSE 0 END) attempted_questions,
                    SUM(CASE WHEN aa.is_correct=1 THEN 1 ELSE 0 END) correct_answers,
                    SUM(CASE WHEN aa.selected_option_ids IS NOT NULL AND aa.selected_option_ids<>'[]' AND IFNULL(aa.is_correct,0)=0 THEN 1 ELSE 0 END) incorrect_answers,
                    SUM(IFNULL(aa.marks_awarded,0)) marks_obtained,
                    SUM(IFNULL(q.marks,0)) total_marks
                FROM content_exam_attempt_answers aa
                JOIN content_exam_questions q ON q.id=aa.question_id
                GROUP BY aa.attempt_id
            ) x ON x.attempt_id=a.id
            SET a.total_questions=x.total_questions,
                a.attempted_questions=x.attempted_questions,
                a.correct_answers=x.correct_answers,
                a.incorrect_answers=x.incorrect_answers,
                a.skipped_questions=GREATEST(0,x.total_questions-x.attempted_questions),
                a.marks_obtained=x.marks_obtained,
                a.total_marks=x.total_marks,
                a.percentage=CASE WHEN x.total_marks>0 THEN ROUND(x.marks_obtained*100/x.total_marks,2) ELSE 0 END,
                a.accuracy_percentage=CASE WHEN x.attempted_questions>0 THEN ROUND(x.correct_answers*100/x.attempted_questions,2) ELSE 0 END,
                a.result_status=CASE WHEN x.marks_obtained>=e.passing_marks THEN 'pass' ELSE 'fail' END,
                a.passed=CASE WHEN x.marks_obtained>=e.passing_marks THEN 1 ELSE 0 END
            WHERE a.submitted_at IS NOT NULL AND a.percentage IS NULL");

        $this->insert_legacy_dimension('topic',
            "LOWER(COALESCE(NULLIF(TRIM(q.topic),''),'general'))",
            "COALESCE(NULLIF(TRIM(q.topic),''),'General')");
        $this->insert_legacy_dimension('difficulty',
            "LOWER(COALESCE(NULLIF(TRIM(q.difficulty),''),'unspecified'))",
            "COALESCE(NULLIF(TRIM(q.difficulty),''),'Unspecified')");

        $this->db->query("INSERT IGNORE INTO content_exam_attempt_insights
            (attempt_id,exam_id,user_id,dimension_type,dimension_key,dimension_label,total_questions,attempted_questions,
             correct_answers,incorrect_answers,skipped_questions,marks_obtained,total_marks,accuracy_percentage,score_percentage,created_at)
            SELECT a.id,a.exam_id,a.user_id,'section',COALESCE(CAST(q.section_id AS CHAR),'0'),
                COALESCE(NULLIF(TRIM(s.title),''),'General'),COUNT(*),
                SUM(CASE WHEN aa.selected_option_ids IS NOT NULL AND aa.selected_option_ids<>'[]' THEN 1 ELSE 0 END),
                SUM(CASE WHEN aa.is_correct=1 THEN 1 ELSE 0 END),
                SUM(CASE WHEN aa.selected_option_ids IS NOT NULL AND aa.selected_option_ids<>'[]' AND IFNULL(aa.is_correct,0)=0 THEN 1 ELSE 0 END),
                SUM(CASE WHEN aa.selected_option_ids IS NULL OR aa.selected_option_ids='[]' THEN 1 ELSE 0 END),
                SUM(IFNULL(aa.marks_awarded,0)),SUM(IFNULL(q.marks,0)),
                ROUND(SUM(CASE WHEN aa.is_correct=1 THEN 1 ELSE 0 END)*100/NULLIF(SUM(CASE WHEN aa.selected_option_ids IS NOT NULL AND aa.selected_option_ids<>'[]' THEN 1 ELSE 0 END),0),2),
                ROUND(SUM(IFNULL(aa.marks_awarded,0))*100/NULLIF(SUM(IFNULL(q.marks,0)),0),2),NOW()
            FROM content_exam_attempts a
            JOIN content_exam_attempt_answers aa ON aa.attempt_id=a.id
            JOIN content_exam_questions q ON q.id=aa.question_id
            LEFT JOIN content_exam_sections s ON s.id=q.section_id
            WHERE a.submitted_at IS NOT NULL
            GROUP BY a.id,a.exam_id,a.user_id,q.section_id,COALESCE(NULLIF(TRIM(s.title),''),'General')");
    }

    private function insert_legacy_dimension($type, $keyExpression, $labelExpression)
    {
        $this->db->query("INSERT IGNORE INTO content_exam_attempt_insights
            (attempt_id,exam_id,user_id,dimension_type,dimension_key,dimension_label,total_questions,attempted_questions,
             correct_answers,incorrect_answers,skipped_questions,marks_obtained,total_marks,accuracy_percentage,score_percentage,created_at)
            SELECT a.id,a.exam_id,a.user_id,?,{$keyExpression},{$labelExpression},COUNT(*),
                SUM(CASE WHEN aa.selected_option_ids IS NOT NULL AND aa.selected_option_ids<>'[]' THEN 1 ELSE 0 END),
                SUM(CASE WHEN aa.is_correct=1 THEN 1 ELSE 0 END),
                SUM(CASE WHEN aa.selected_option_ids IS NOT NULL AND aa.selected_option_ids<>'[]' AND IFNULL(aa.is_correct,0)=0 THEN 1 ELSE 0 END),
                SUM(CASE WHEN aa.selected_option_ids IS NULL OR aa.selected_option_ids='[]' THEN 1 ELSE 0 END),
                SUM(IFNULL(aa.marks_awarded,0)),SUM(IFNULL(q.marks,0)),
                ROUND(SUM(CASE WHEN aa.is_correct=1 THEN 1 ELSE 0 END)*100/NULLIF(SUM(CASE WHEN aa.selected_option_ids IS NOT NULL AND aa.selected_option_ids<>'[]' THEN 1 ELSE 0 END),0),2),
                ROUND(SUM(IFNULL(aa.marks_awarded,0))*100/NULLIF(SUM(IFNULL(q.marks,0)),0),2),NOW()
            FROM content_exam_attempts a
            JOIN content_exam_attempt_answers aa ON aa.attempt_id=a.id
            JOIN content_exam_questions q ON q.id=aa.question_id
            WHERE a.submitted_at IS NOT NULL
            GROUP BY a.id,a.exam_id,a.user_id,{$keyExpression},{$labelExpression}", array($type));
    }
}
