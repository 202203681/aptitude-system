<?php
/**
 * Adaptive Testing Engine using Item Response Theory (Rasch Model)
 * Implements maximum information adaptive item selection
 */

class AdaptiveEngine {
    private $conn;
    private $ability_estimate; // θ
    private $standard_error;   // SE
    private $items_administered;  // Keep as private, use getter/setter
    private $max_items;
    private $stop_se;
    private $responses;
    private $category;
    private $administered_ids;  // Track IDs separately
    
    /**
     * Constructor - Initialize adaptive test
     */
    public function __construct($conn, $category = null, $max_items = 30, $stop_se = 0.30) {
        $this->conn = $conn;
        $this->ability_estimate = 0.0; // Initial θ = 0
        $this->standard_error = 0.5;    // Initial SE
        $this->items_administered = [];
        $this->administered_ids = [];
        $this->responses = [];
        $this->max_items = $max_items;
        $this->stop_se = $stop_se;
        $this->category = $category;
    }
    
    /**
     * Calculate probability of correct response using Rasch model
     * P(θ) = exp(θ - b) / [1 + exp(θ - b)]
     */
    public function calculateProbability($ability, $difficulty) {
        $z = $ability - $difficulty;
        if ($z > 30) return 1.0;
        if ($z < -30) return 0.0;
        return exp($z) / (1 + exp($z));
    }
    
    /**
     * Calculate item information function
     * I(θ) = P(θ) × (1 - P(θ))
     */
    public function calculateInformation($ability, $difficulty) {
        $p = $this->calculateProbability($ability, $difficulty);
        return $p * (1 - $p);
    }
    
    /**
     * Update ability estimate using Newton-Raphson method
     */
    public function updateAbility($response, $difficulty) {
        $p = $this->calculateProbability($this->ability_estimate, $difficulty);
        $u = $response ? 1 : 0;
        $denominator = $p * (1 - $p);
        
        if ($denominator > 0) {
            $update = ($u - $p) / $denominator;
            $this->ability_estimate += $update;
        }
        
        // Update standard error
        $total_info = 0;
        foreach ($this->items_administered as $item) {
            $total_info += $this->calculateInformation($this->ability_estimate, $item['difficulty']);
        }
        $this->standard_error = $total_info > 0 ? 1 / sqrt($total_info) : 0.5;
        
        return $this->ability_estimate;
    }
    
    /**
     * Select next item maximizing information at current ability
     * FIXED: Ensures unique questions are selected
     */
    public function selectNextItem() {
        // Build list of already administered question IDs
        $exclude_ids = [];
        foreach ($this->items_administered as $item) {
            $exclude_ids[] = $item['id'];
        }
        
        $exclude_sql = '';
        if (!empty($exclude_ids)) {
            $exclude_sql = "AND id NOT IN (" . implode(',', $exclude_ids) . ")";
        }
        
        // Build category filter
        $category_sql = '';
        if ($this->category && $this->category != 'all' && $this->category != '') {
            $category_sql = "AND category = '" . $this->conn->real_escape_string($this->category) . "'";
        }
        
        // Get questions not yet administered, ordered by closest difficulty to current ability
        $sql = "SELECT id, question, option_a, option_b, option_c, option_d, correct_answer, 
                       difficulty, discrimination, explanation, category, topic
                FROM questions 
                WHERE active = 1 
                $category_sql
                $exclude_sql
                ORDER BY ABS(difficulty - {$this->ability_estimate}) ASC
                LIMIT 10";
        
        $result = $this->conn->query($sql);
        
        if (!$result || $result->num_rows == 0) {
            // No more unique questions found
            return null;
        }
        
        // Find the item with maximum information at current ability
        $best_item = null;
        $max_info = -1;
        
        while ($item = $result->fetch_assoc()) {
            $info = $this->calculateInformation($this->ability_estimate, $item['difficulty']);
            if ($info > $max_info) {
                $max_info = $info;
                $best_item = $item;
            }
        }
        
        // If no item found, take the first one
        if (!$best_item && $result->num_rows > 0) {
            $result->data_seek(0);
            $best_item = $result->fetch_assoc();
        }
        
        // Store the selected item
        if ($best_item) {
            $this->items_administered[] = [
                'id' => $best_item['id'],
                'difficulty' => $best_item['difficulty'],
                'discrimination' => $best_item['discrimination'],
                'category' => $best_item['category']
            ];
        }
        
        return $best_item;
    }
    
    /**
     * Process a response and update estimates
     */
    public function processResponse($question_id, $user_answer, $correct_answer) {
        // Find the question in administered items
        $question = null;
        foreach ($this->items_administered as $item) {
            if ($item['id'] == $question_id) {
                $question = $item;
                break;
            }
        }
        
        if (!$question) {
            // Fetch from database
            $stmt = $this->conn->prepare("SELECT difficulty, correct_answer FROM questions WHERE id = ?");
            $stmt->bind_param("i", $question_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $question = $result->fetch_assoc();
        }
        
        $is_correct = ($user_answer === $correct_answer);
        
        // Update ability estimate
        $this->updateAbility($is_correct, $question['difficulty']);
        
        // Store response
        $this->responses[$question_id] = [
            'is_correct' => $is_correct,
            'answer' => $user_answer
        ];
        
        return [
            'ability' => $this->ability_estimate,
            'se' => $this->standard_error,
            'is_correct' => $is_correct
        ];
    }
    
    /**
     * Check if test should stop
     */
    public function shouldStop() {
        $item_count = count($this->items_administered);
        return ($item_count >= $this->max_items) || 
               ($this->standard_error < $this->stop_se && $item_count >= 10);
    }
    
    /**
     * Get current ability estimate
     */
    public function getAbility() {
        return $this->ability_estimate;
    }
    
    /**
     * Get current standard error
     */
    public function getStandardError() {
        return $this->standard_error;
    }
    
    /**
     * Get number of items administered
     */
    public function getItemsCount() {
        return count($this->items_administered);
    }
    
    /**
     * Get all administered items
     */
    public function getAdministeredItems() {
        return $this->items_administered;
    }
    
    /**
     * Get final scaled score (μ=50, σ=10)
     */
    public function getScaledScore() {
        return 50 + ($this->ability_estimate * 10);
    }
    
    /**
     * Get percentile rank based on ability estimate
     */
    public function getPercentileRank() {
        $z = $this->ability_estimate;
        $p = 0.5 * (1 + $this->erf($z / sqrt(2)));
        return min(99, max(1, round($p * 100)));
    }
    
    /**
     * Error function for normal distribution
     */
    private function erf($x) {
        $t = 1.0 / (1.0 + 0.5 * abs($x));
        $tau = $t * exp(-$x * $x - 1.26551223 + $t * (1.00002368 + $t * (0.37409196 + $t * (0.09678418 + $t * (-0.18628806 + $t * (0.27886807 + $t * (-1.13520398 + $t * (1.48851587 + $t * (-0.82215223 + $t * 0.17087277)))))))));
        return $x >= 0 ? 1 - $tau : $tau - 1;
    }
    
    /**
     * Get grade based on percentage
     */
    public function getGrade($percentage) {
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        return 'F';
    }
    
    /**
     * Get career recommendations based on ability and category
     */
    public function getCareerRecommendations($ability, $category, $percentage) {
        $recommendations = [];
        
        if ($category == 'Quantitative Aptitude') {
            if ($percentage >= 70) {
                $recommendations = ['Data Scientist', 'Actuary', 'Financial Analyst', 'Software Engineer', 'Statistician'];
            } elseif ($percentage >= 50) {
                $recommendations = ['Accountant', 'Banker', 'Business Analyst', 'Economist', 'Engineer'];
            } else {
                $recommendations = ['Retail Manager', 'Administrator', 'Sales Associate', 'Customer Service'];
            }
        } elseif ($category == 'Logical Reasoning') {
            if ($percentage >= 70) {
                $recommendations = ['Lawyer', 'Judge', 'Detective', 'IT Security Analyst', 'Researcher'];
            } elseif ($percentage >= 50) {
                $recommendations = ['Police Officer', 'Paralegal', 'Programmer', 'Manager', 'Consultant'];
            } else {
                $recommendations = ['Customer Support', 'Administrative Assistant', 'Sales', 'Technician'];
            }
        } else { // Verbal Ability
            if ($percentage >= 70) {
                $recommendations = ['Journalist', 'Content Writer', 'Translator', 'Public Relations Specialist', 'Lawyer'];
            } elseif ($percentage >= 50) {
                $recommendations = ['Teacher', 'Editor', 'Marketing Specialist', 'Copywriter', 'Communications Officer'];
            } else {
                $recommendations = ['Receptionist', 'Call Center Agent', 'Administrative Assistant', 'Sales Associate'];
            }
        }
        
        return $recommendations;
    }
}
?>