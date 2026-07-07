-- Additional Domains and Questions
-- Add new domains to the questions table if not exists
ALTER TABLE questions 
ADD COLUMN IF NOT EXISTS sub_domain VARCHAR(100),
ADD COLUMN IF NOT EXISTS difficulty_parameter DECIMAL(5,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS discrimination_parameter DECIMAL(5,2) DEFAULT 1;

-- Insert questions for Quantitative Aptitude (more questions)
INSERT INTO questions (category, topic, sub_domain, question, option_a, option_b, option_c, option_d, correct_answer, difficulty, discrimination, explanation) VALUES
('Quantitative Aptitude', 'Percentages', 'Percentage Calculations', 'If 20% of a number is 60, what is the number?', '200', '300', '400', '500', '300', -1.2, 1.0, '20% = 60, so 100% = 60 × 5 = 300'),
('Quantitative Aptitude', 'Percentages', 'Percentage Change', 'A shirt originally costing $80 is on sale at 25% off. What is the sale price?', '$55', '$60', '$65', '$70', '$60', -0.8, 1.0, '25% of 80 = 20, so 80 - 20 = 60'),
('Quantitative Aptitude', 'Percentages', 'Profit and Loss', 'A trader buys goods for $200 and sells them for $250. What is the profit percentage?', '20%', '25%', '30%', '35%', '25%', -0.5, 1.0, 'Profit = 50, Profit% = (50/200) × 100 = 25%'),
('Quantitative Aptitude', 'Percentages', 'Compound Interest', 'What is the compound interest on $1000 at 10% per annum for 2 years?', '$200', '$210', '$220', '$230', '$210', 0.2, 1.0, 'A = 1000(1.1)² = 1210, CI = 210'),
('Quantitative Aptitude', 'Algebra', 'Linear Equations', 'Solve for x: 2x + 5 = 15', '3', '4', '5', '6', '5', -1.0, 1.0, '2x = 10, x = 5'),
('Quantitative Aptitude', 'Algebra', 'Quadratic Equations', 'What are the roots of x² - 5x + 6 = 0?', '2,3', '1,6', '-2,-3', '2,-3', '2,3', 0.5, 1.0, '(x-2)(x-3)=0, so x=2 or x=3'),
('Quantitative Aptitude', 'Algebra', 'Simultaneous Equations', 'Solve: x + y = 10, x - y = 4', 'x=7,y=3', 'x=6,y=4', 'x=8,y=2', 'x=5,y=5', 'x=7,y=3', 0.8, 1.0, 'Adding equations: 2x=14, x=7, y=3'),
('Quantitative Aptitude', 'Ratios', 'Ratio Problems', 'If a:b = 2:3 and b:c = 4:5, find a:c', '8:15', '2:5', '4:9', '8:12', '8:15', 1.0, 1.0, 'a:b = 2:3 = 8:12, b:c = 4:5 = 12:15, so a:c = 8:15'),
('Quantitative Aptitude', 'Ratios', 'Proportion', 'If 5 workers can complete a job in 12 days, how many days will 10 workers take?', '4 days', '5 days', '6 days', '8 days', '6 days', -0.2, 1.0, 'Workers × Days = constant, 5×12=60, 10×d=60, d=6'),
('Quantitative Aptitude', 'Averages', 'Mean', 'Find the average of 10, 20, 30, 40, 50', '25', '30', '35', '40', '30', -1.2, 1.0, 'Sum = 150, count = 5, average = 30'),
('Quantitative Aptitude', 'Averages', 'Weighted Average', 'A student scores 80 in Math (weight 3) and 90 in Science (weight 2). Find weighted average.', '82', '84', '86', '88', '84', 0.0, 1.0, '(80×3 + 90×2) / 5 = (240+180)/5 = 420/5 = 84'),
('Quantitative Aptitude', 'Simple Interest', 'Interest Calculation', 'Find simple interest on $500 at 5% for 3 years', '$50', '$75', '$100', '$125', '$75', -0.8, 1.0, 'SI = P×R×T/100 = 500×5×3/100 = 75'),
('Quantitative Aptitude', 'Geometry', 'Area of Circle', 'Find the area of a circle with radius 7 cm (π=22/7)', '154 cm²', '144 cm²', '164 cm²', '174 cm²', '154 cm²', 0.2, 1.0, 'Area = πr² = 22/7 × 7 × 7 = 154'),
('Quantitative Aptitude', 'Geometry', 'Pythagoras Theorem', 'In a right triangle, legs are 3 and 4. Find hypotenuse.', '5', '6', '7', '8', '5', -1.0, 1.0, 'Hypotenuse = √(3²+4²)=√25=5'),
('Quantitative Aptitude', 'Data Interpretation', 'Bar Graph', 'A bar graph shows sales: Jan=100, Feb=150, Mar=200. What is average monthly sales?', '100', '125', '150', '175', '150', 0.5, 1.0, 'Average = (100+150+200)/3 = 450/3 = 150'),
('Quantitative Aptitude', 'Data Interpretation', 'Percentage from Graph', 'If total sales = 500, and product A sales = 125, what percentage is product A?', '20%', '25%', '30%', '35%', '25%', 0.0, 1.0, '(125/500) × 100 = 25%'),

-- Logical Reasoning Questions
('Logical Reasoning', 'Number Series', 'Pattern Recognition', 'Find the next number: 2, 4, 8, 16, ?', '24', '28', '30', '32', '32', -1.0, 1.0, 'Each number is multiplied by 2'),
('Logical Reasoning', 'Number Series', 'Arithmetic Sequence', 'Find the next number: 5, 10, 15, 20, ?', '22', '23', '24', '25', '25', -1.2, 1.0, 'Add 5 each time'),
('Logical Reasoning', 'Number Series', 'Geometric Sequence', 'Find the next number: 81, 27, 9, 3, ?', '0', '1', '2', '3', '1', -0.5, 1.0, 'Divide by 3 each time'),
('Logical Reasoning', 'Number Series', 'Fibonacci Pattern', 'Find the next number: 1, 1, 2, 3, 5, ?', '6', '7', '8', '9', '8', 0.2, 1.0, 'Add previous two numbers: 3+5=8'),
('Logical Reasoning', 'Blood Relations', 'Family Tree', 'A is the father of B. C is the mother of B. What is C to A?', 'Sister', 'Wife', 'Daughter', 'Mother', 'Wife', -0.5, 1.0, 'Parents of B: A is father, C is mother, so C is wife of A'),
('Logical Reasoning', 'Blood Relations', 'Cousin Relations', 'Your mother\'s brother is your...', 'Uncle', 'Aunt', 'Cousin', 'Grandfather', 'Uncle', -0.2, 1.0, 'Mother\'s brother is maternal uncle'),
('Logical Reasoning', 'Blood Relations', 'Grandparent Relation', 'Your father\'s father is your...', 'Uncle', 'Grandfather', 'Cousin', 'Brother', 'Grandfather', -0.8, 1.0, 'Father\'s father is paternal grandfather'),
('Logical Reasoning', 'Coding-Decoding', 'Letter Coding', 'If CAT is coded as DBU, how is DOG coded?', 'EPH', 'EPI', 'EPJ', 'EPK', 'EPH', 0.5, 1.0, 'Each letter moves +1 position'),
('Logical Reasoning', 'Coding-Decoding', 'Number Coding', 'If 123 = 6, 234 = 9, then 345 = ?', '10', '11', '12', '13', '12', 0.8, 1.0, 'Sum of digits: 1+2+3=6, 2+3+4=9, 3+4+5=12'),
('Logical Reasoning', 'Direction Sense', 'North-South', 'A person walks 10m North, then 10m East, then 10m South. Where is he from start?', '5m East', '10m East', '10m West', 'Start point', '10m East', 0.0, 1.0, 'Net displacement: 10m East'),
('Logical Reasoning', 'Direction Sense', 'Turns', 'If you face North and turn 90° clockwise, which direction do you face?', 'North', 'East', 'South', 'West', 'East', -1.0, 1.0, 'Clockwise from North = East'),
('Logical Reasoning', 'Analogy', 'Word Analogy', 'Doctor : Hospital :: Teacher : ?', 'School', 'Office', 'Bank', 'Market', 'School', -0.8, 1.0, 'Doctor works in hospital, teacher works in school'),
('Logical Reasoning', 'Analogy', 'Number Analogy', '3:9 :: 4:?', '12', '14', '16', '18', '16', 0.2, 1.0, '3²=9, 4²=16'),
('Logical Reasoning', 'Puzzles', 'Seating Arrangement', 'In a row of 5 seats, A sits in the middle. Who sits to A\'s right if order is A,B,C,D,E?', 'B', 'C', 'D', 'E', 'B', 1.2, 1.0, 'Middle is 3rd seat, B is 2nd seat? Actually need to interpret carefully'),
('Logical Reasoning', 'Odd One Out', 'Classification', 'Which is the odd one out? Apple, Orange, Banana, Carrot', 'Apple', 'Orange', 'Banana', 'Carrot', 'Carrot', -0.5, 1.0, 'Carrot is a vegetable, others are fruits'),
('Logical Reasoning', 'Odd One Out', 'Number Classification', 'Which is odd? 2, 4, 6, 9', '2', '4', '6', '9', '9', -0.8, 1.0, '9 is odd, others are even'),

-- Verbal Ability Questions
('Verbal Ability', 'Synonyms', 'Vocabulary', 'What is the synonym of "Happy"?', 'Sad', 'Angry', 'Joyful', 'Tired', 'Joyful', -1.0, 1.0, 'Happy means joyful'),
('Verbal Ability', 'Synonyms', 'Advanced Vocabulary', 'What is the synonym of "Benevolent"?', 'Cruel', 'Kind', 'Selfish', 'Greedy', 'Kind', 0.2, 1.0, 'Benevolent means kind and generous'),
('Verbal Ability', 'Synonyms', 'Formal Vocabulary', 'What is the synonym of "Commence"?', 'End', 'Start', 'Stop', 'Pause', 'Start', -0.2, 1.0, 'Commence means begin or start'),
('Verbal Ability', 'Antonyms', 'Opposites', 'What is the antonym of "Dark"?', 'Night', 'Black', 'Light', 'Shadow', 'Light', -1.0, 1.0, 'Dark opposite is light'),
('Verbal Ability', 'Antonyms', 'Complex Opposites', 'What is the antonym of "Artificial"?', 'Fake', 'Real', 'Synthetic', 'Man-made', 'Real', 0.5, 1.0, 'Artificial means not real, opposite is real'),
('Verbal Ability', 'Antonyms', 'Advanced Antonyms', 'What is the antonym of "Explicit"?', 'Clear', 'Obvious', 'Vague', 'Direct', 'Vague', 0.8, 1.0, 'Explicit means clearly stated, opposite is vague'),
('Verbal Ability', 'Spellings', 'Common Misspellings', 'Which is the correct spelling?', 'Recieve', 'Receive', 'Receeve', 'Receeve', 'Receive', -0.8, 1.0, 'Correct spelling: Receive (i before e except after c)'),
('Verbal Ability', 'Spellings', 'Difficult Words', 'Which is the correct spelling?', 'Accommodate', 'Acommodate', 'Accommodate', 'Acomodate', 'Accommodate', 0.0, 1.0, 'Accommodate has double c and double m'),
('Verbal Ability', 'Spellings', 'Advanced Words', 'Which is the correct spelling?', 'Neccessary', 'Necessary', 'Neccessery', 'Necessery', 'Necessary', 0.3, 1.0, 'Correct spelling: Necessary (one c, double s)'),
('Verbal Ability', 'Sentence Completion', 'Grammar', 'She _____ to school every day.', 'go', 'goes', 'going', 'went', 'goes', -0.5, 1.0, 'Present tense, third person singular'),
('Verbal Ability', 'Sentence Completion', 'Vocabulary in Context', 'The weather was _____; we had to cancel the picnic.', 'beautiful', 'sunny', 'terrible', 'pleasant', 'terrible', -0.2, 1.0, 'Canceling picnic implies bad weather'),
('Verbal Ability', 'Sentence Completion', 'Advanced Context', 'His _____ remarks offended everyone in the room.', 'tactful', 'diplomatic', 'insulting', 'polite', 'insulting', 0.5, 1.0, 'Offending implies insulting remarks'),
('Verbal Ability', 'Reading Comprehension', 'Main Idea', 'A passage discusses climate change effects. What is the main idea?', 'Weather patterns', 'Global warming impact', 'Seasonal changes', 'Rainfall', 'Global warming impact', 0.2, 1.0, 'Climate change is about global warming impact'),
('Verbal Ability', 'Idioms', 'Common Idioms', 'What does "break the ice" mean?', 'Start a conversation', 'Break something', 'Freeze water', 'End a fight', 'Start a conversation', -0.5, 1.0, 'Break the ice means to start conversation'),
('Verbal Ability', 'Idioms', 'Business Idioms', 'What does "think outside the box" mean?', 'Think creatively', 'Think inside', 'Think normally', 'Think slowly', 'Think creatively', 0.2, 1.0, 'Think outside the box means creative thinking'),
('Verbal Ability', 'Parts of Speech', 'Nouns', 'Which word is a noun?', 'Run', 'Beautiful', 'Happiness', 'Quickly', 'Happiness', -0.8, 1.0, 'Happiness is a noun (abstract)'),
('Verbal Ability', 'Parts of Speech', 'Verbs', 'Which word is a verb?', 'Beautiful', 'Running', 'Quickly', 'Happiness', 'Running', -0.5, 1.0, 'Running is a verb (action word)'),

-- Additional Domains: Analytical Reasoning
('Logical Reasoning', 'Analytical Reasoning', 'Syllogisms', 'All cats are mammals. Some mammals are dogs. Which is true?', 'Some cats are dogs', 'All dogs are cats', 'Some mammals are cats', 'No conclusion', 'Some mammals are cats', 1.0, 1.0, 'From statements: cats are subset of mammals'),
('Logical Reasoning', 'Analytical Reasoning', 'Logical Deduction', 'If it rains, ground gets wet. Ground is wet. What can we conclude?', 'It rained', 'It may have rained', 'It didn\'t rain', 'Ground is dry', 'It may have rained', 0.8, 1.0, 'Wet ground could be from other causes'),
('Logical Reasoning', 'Analytical Reasoning', 'Assumptions', 'Statement: "Use XYZ brand for better results." Assumption?', 'XYZ is cheap', 'Other brands are bad', 'Results can improve', 'Everyone uses XYZ', 'Results can improve', 0.5, 1.0, 'The claim implies results can improve'),
('Quantitative Aptitude', 'Advanced Math', 'Probability', 'What is probability of getting heads on a coin toss?', '1/3', '1/2', '2/3', '1/4', '1/2', -1.0, 1.0, 'Two outcomes, one favorable'),
('Quantitative Aptitude', 'Advanced Math', 'Permutations', 'How many ways to arrange letters of "CAT"?', '3', '4', '5', '6', '6', 0.5, 1.0, '3! = 6 arrangements'),
('Quantitative Aptitude', 'Advanced Math', 'Combinations', 'How many ways to choose 2 from 4 items?', '4', '6', '8', '12', '6', 0.8, 1.0, 'C(4,2) = 6'),
('Verbal Ability', 'Critical Reasoning', 'Strengthening Arguments', 'Which strengthens "Exercise improves health"?', 'Exercise causes injuries', 'Studies show fit people live longer', 'Exercise is expensive', 'People dislike exercise', 'Studies show fit people live longer', 0.5, 1.0, 'Evidence of longer life supports claim');

-- Add index for better performance
CREATE INDEX IF NOT EXISTS idx_questions_category ON questions(category);
CREATE INDEX IF NOT EXISTS idx_questions_topic ON questions(topic);
CREATE INDEX IF NOT EXISTS idx_results_user_id ON results(user_id);
CREATE INDEX IF NOT EXISTS idx_results_date ON results(date);