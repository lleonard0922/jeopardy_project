# jeopardy_project
##  Overview
This project is a dynamic Jeopardy-style quiz game built using PHP, HTML, CSS, and JavaScript. It allows players to select questions from a game board, answer them, and earn points based on correctness. The game includes features like score tracking, a leaderboard, background music, and an AI assistant that provides fun facts for each question.

---

##  Features
- Interactive game board with selectable questions
- Score tracking system for players
- Turn-based gameplay logic
- Answer validation (correct/incorrect checking)
- Questions are disabled after being answered
- Final "Who Won" screen
- Leaderboard that stores and displays top scores
- Background music with toggle (on/off)
- AI assistant that provides facts for each question

---

##  How It Works
The project uses PHP as the main logic controller. When the game starts, PHP loads all questions, answers, and player data. As players interact with the game, PHP processes selections, checks answers, updates scores, and tracks which questions have already been used.

Sessions are used to store game progress such as:
- Player scores
- Current turn
- Answered questions

At the end of the game, PHP calculates the winner and displays the final results along with the leaderboard.

---

##  Technologies Used
- **PHP** – Backend logic and game flow
- **HTML** – Structure of the game
- **CSS** – Styling and layout
- **Sessions** – State management

- 
