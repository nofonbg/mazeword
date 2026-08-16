<?php
namespace mod_mazeword\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {

    protected function render_game_view(game_view $game): string {
        global $OUTPUT, $PAGE;

        $instance = $game->instance;
        $mazeword = $instance->mazeword;
        $config = $instance->get_game_config();

        $visitedcells = isset($game->attempt->visitedcells) ? $game->attempt->visitedcells : '[]';
        $solutionvisited = isset($game->attempt->solutionvisited) ? $game->attempt->solutionvisited : '[]';
        $clearedcells = isset($game->attempt->clearedcells) ? $game->attempt->clearedcells : '[]';
        $collectedletters = isset($game->attempt->collectedletters) ? $game->attempt->collectedletters : '[]';
        $movetrail = isset($game->attempt->movetrail) ? $game->attempt->movetrail : '[]';

        $output = '';

        $output .= '<style>
.mazeword-container{position:relative;padding:10px;background:#34495e;border-radius:8px;box-shadow:0 10px 20px rgba(0,0,0,.3);display:flex;justify-content:center;align-items:center;margin:20px auto;max-width:600px}
.mazeword-grid{display:grid;grid-template-columns:repeat(20,1fr);grid-template-rows:repeat(20,1fr);gap:1px;background-color:#1a252f;width:90vw;height:90vw;max-width:500px;max-height:500px;border:2px solid #1a252f;z-index:1}
.mazeword-firework-canvas{position:absolute;top:10px;left:10px;width:calc(90vw - 0px);height:calc(90vw - 0px);max-width:500px;max-height:500px;pointer-events:none;z-index:20}
.cell{width:100%;height:100%;background-color:#ecf0f1;display:flex;justify-content:center;align-items:center;font-weight:bold;font-size:.8rem;color:#2c3e50;user-select:none;position:relative}
.wall{background-color:#1a252f;color:transparent}
.start{background-color:#27ae60;color:white}
.goal{background-color:#c0392b;color:white}
.player{background-color:rgba(41,128,185,.7);color:white;border-radius:4px;z-index:10}
.mazeword-solution-tracker{text-align:center;margin:15px 0;padding:10px;background:#34495e;border-radius:8px;color:#ecf0f1}
.mazeword-tracker-label{font-weight:bold;margin-right:10px}
.mazeword-tracker-letter{display:inline-block;width:28px;height:28px;line-height:28px;margin:0 2px;background:#ecf0f1;color:#2c3e50;border-radius:4px;font-weight:bold;font-size:.9rem;transition:background-color .3s,color .3s}
.mazeword-tracker-letter.visited{background:#c0392b;color:white}
.mazeword-status{margin-top:15px;font-weight:bold;height:20px;color:#f1c40f;text-align:center;font-size:1.1rem}
.mazeword-controls{margin-top:20px;display:grid;grid-template-columns:repeat(3,60px);grid-template-rows:repeat(2,60px);gap:10px;justify-content:center}
.mazeword-btn{background-color:#34495e;border:none;border-radius:8px;color:white;font-size:24px;cursor:pointer;display:flex;justify-content:center;align-items:center;transition:transform .1s,background-color .2s;user-select:none}
.mazeword-btn:active{transform:scale(.95);background-color:#2c3e50}
.mazeword-btn-up{grid-column:2;grid-row:1}
.mazeword-btn-left{grid-column:1;grid-row:2}
.mazeword-btn-down{grid-column:2;grid-row:2}
.mazeword-btn-right{grid-column:3;grid-row:2}
.mazeword-actions{text-align:center;margin-top:15px}
.mazeword-reset-btn{padding:10px 30px;background-color:#c0392b;border:none;border-radius:8px;color:white;font-size:1rem;font-weight:bold;text-transform:uppercase;letter-spacing:1px;cursor:pointer;transition:opacity .2s}
.mazeword-reset-btn:hover{opacity:.9}
.mazeword-stats{margin-top:30px;padding:20px;background:#ecf0f1;border-radius:8px;max-width:500px;margin-left:auto;margin-right:auto}
.mazeword-stats h3{margin-top:0;color:#2c3e50}
.mazeword-stats table{width:100%}
.mazeword-stats td{padding:5px 10px}
@media(min-width:600px){.mazeword-controls{grid-template-columns:repeat(3,70px);grid-template-rows:repeat(2,70px)}.cell{font-size:1rem}.mazeword-tracker-letter{width:35px;height:35px;line-height:35px;font-size:1rem}}
</style>';

        $output .= $OUTPUT->heading(format_string($mazeword->name));

        if ($mazeword->intro) {
            $output .= $OUTPUT->box_start('mod_introbox', 'mazewordintro');
            $output .= format_module_intro('mazeword', $mazeword, $instance->cm->id);
            $output .= $OUTPUT->box_end();
        }

        $output .= '<div id="mazeword-container" class="mazeword-container">';
        $output .= '<div id="mazeword-grid" class="mazeword-grid"></div>';
        $output .= '<canvas id="mazeword-firework-canvas" class="mazeword-firework-canvas"></canvas>';
        $output .= '</div>';

        $output .= '<div id="mazeword-status" class="mazeword-status"></div>';

        $output .= '<div id="mazeword-controls" class="mazeword-controls">';
        $output .= '<button class="btn mazeword-btn mazeword-btn-up" data-dir="up" aria-label="Up">&#9650;</button>';
        $output .= '<button class="btn mazeword-btn mazeword-btn-left" data-dir="left" aria-label="Left">&#9664;</button>';
        $output .= '<button class="btn mazeword-btn mazeword-btn-down" data-dir="down" aria-label="Down">&#9660;</button>';
        $output .= '<button class="btn mazeword-btn mazeword-btn-right" data-dir="right" aria-label="Right">&#9654;</button>';
        $output .= '</div>';

        $output .= '<div class="mazeword-actions">';
        $output .= '<button id="mazeword-reset-btn" class="btn mazeword-reset-btn">' .
                   get_string('neustart', 'mod_mazeword') . '</button>';
        $output .= '</div>';

        if ($game->isteacher) {
            $stats = $instance->get_statistics();
            $output .= '<div class="mazeword-stats">';
            $output .= '<h3>' . get_string('teacherview', 'mod_mazeword') . '</h3>';
            $output .= '<table class="generaltable">';
            $output .= '<tr><td>' . get_string('totalenrolled', 'mod_mazeword') .
                       ':</td><td>' . $stats->total_enrolled . '</td></tr>';
            $output .= '<tr><td>' . get_string('totalattempts', 'mod_mazeword') .
                       ':</td><td>' . $stats->total_attempts . '</td></tr>';
            $output .= '<tr><td>' . get_string('completedattempts', 'mod_mazeword') .
                       ':</td><td>' . $stats->completed_attempts . '</td></tr>';
            $output .= '</table>';
            $output .= '</div>';
        }

        $jsconfig = json_encode($config);
        $output .= <<<JS
<script>
(function() {
    var config = {$jsconfig};
    var savedX = {$game->attempt->playerx};
    var savedY = {$game->attempt->playery};
    var visitedJson = '{$visitedcells}';
    var solutionJson = '{$solutionvisited}';
    var clearedJson = '{$clearedcells}';
    var collectedJson = '{$collectedletters}';
    var trailJson = '{$movetrail}';
    var completed = {$game->attempt->completed};

    var game = null;

    function MazeGame(config, savedX, savedY, visitedCells, solutionVisited, clearedCells, collectedLetters, moveTrail, isCompleted) {
        this.config = config;
        this.size = config.mazesize;
        this.solutionWord = config.solutionword;
        this.wrongWords = [config.wrongword1, config.wrongword2, config.wrongword3];
        this.cmid = config.cmid;
        this.sesskey = config.sesskey;
        this.grid = [];
        this.charGrid = [];
        this.solutionWordCells = {};
        this.visitedSolutionSet = {};
        this.visitedCellsSet = {};
        this.collectedLetters = [];
        this.clearedCells = {};
        this.moveTrail = [];
        var i;
        for (i = 0; i < solutionVisited.length; i++) this.visitedSolutionSet[solutionVisited[i]] = true;
        for (i = 0; i < visitedCells.length; i++) this.visitedCellsSet[visitedCells[i]] = true;
        for (i = 0; i < clearedCells.length; i++) this.clearedCells[clearedCells[i]] = true;
        for (i = 0; i < collectedLetters.length; i++) this.collectedLetters.push(collectedLetters[i]);
        this.playerPos = {x: savedX, y: savedY};
        this.moveTrail = moveTrail.length > 0 ? moveTrail : [{x: savedX, y: savedY}];
        this.goalPos = {x: this.size - 2, y: this.size - 2};
        this.isGameOver = isCompleted;
        this.isFirstMove = !isCompleted && savedX === 0 && savedY === 0;
        this.gridElement = document.getElementById('mazeword-grid');
        this.statusElement = document.getElementById('mazeword-status');
        this.canvas = document.getElementById('mazeword-firework-canvas');
        this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
        this.particles = [];
        this.initGame();
    }

    MazeGame.prototype.initGame = function() {
        this.generateMaze();
        this.addExtraDeadEnds();
        var solutionPath = this.findPath(this.playerPos, this.goalPos);
        var branches = this.findBranches(solutionPath);
        this.placeWords(solutionPath, branches);
        for (var k in this.clearedCells) {
            var parts = k.split(',');
            var cx = parseInt(parts[0]), cy = parseInt(parts[1]);
            if (this.charGrid[cy] && this.charGrid[cy][cx] !== undefined) {
                this.charGrid[cy][cx] = '';
            }
        }
        for (var c = 0; c < this.collectedLetters.length; c++) {
            var cl = this.collectedLetters[c];
            if (this.charGrid[cl.y] && this.charGrid[cl.y][cl.x] !== undefined) {
                this.charGrid[cl.y][cl.x] = '';
            }
        }
        this.render();
        this.updateSolutionTracker();
        if (this.isGameOver) {
            this.statusElement.textContent = 'Gewonnen! Das Wort war: ' + this.solutionWord;
            this.statusElement.style.color = '#2ecc71';
            this.startFirework();
        } else if (this.isFirstMove) {
            this.statusElement.textContent = 'Spiel gestartet! Finde den Weg des Lösungswortes.';
            this.statusElement.style.color = 'rgb(0, 164, 18)';
        } else {
            this.statusElement.textContent = 'Spiel fortgesetzt.';
            this.statusElement.style.color = '#f1c40f';
        }
    };

    MazeGame.prototype.generateMaze = function() {
        var y, x;
        this.grid = [];
        for (y = 0; y < this.size; y++) {
            this.grid[y] = [];
            for (x = 0; x < this.size; x++) this.grid[y][x] = 0;
        }
        var stack = [];
        this.grid[0][0] = 1;
        stack.push({x: 0, y: 0});
        var dirs = [{dx:0,dy:-2},{dx:0,dy:2},{dx:-2,dy:0},{dx:2,dy:0}];
        while (stack.length > 0) {
            var current = stack[stack.length - 1];
            var shuffled = dirs.slice().sort(function() { return Math.random() - 0.5; });
            var moved = false;
            for (var d = 0; d < shuffled.length; d++) {
                var dir = shuffled[d];
                var nx = current.x + dir.dx;
                var ny = current.y + dir.dy;
                if (nx >= 0 && nx < this.size && ny >= 0 && ny < this.size && this.grid[ny][nx] === 0) {
                    this.grid[current.y + dir.dy / 2][current.x + dir.dx / 2] = 1;
                    this.grid[ny][nx] = 1;
                    stack.push({x: nx, y: ny});
                    moved = true;
                    break;
                }
            }
            if (!moved) stack.pop();
        }
    };

    MazeGame.prototype.addExtraDeadEnds = function() {
        var pathCells = [];
        var y, x;
        for (y = 0; y < this.size; y++) {
            for (x = 0; x < this.size; x++) {
                if (this.grid[y][x] === 1) pathCells.push({x: x, y: y});
            }
        }
        pathCells.sort(function() { return Math.random() - 0.5; });
        var dirs = [{dx:0,dy:1},{dx:0,dy:-1},{dx:1,dy:0},{dx:-1,dy:0}];
        for (var i = 0; i < 15 && i < pathCells.length; i++) {
            var start = pathCells[i];
            var dir = dirs[Math.floor(Math.random() * dirs.length)];
            var cx = start.x, cy = start.y;
            var len = Math.floor(Math.random() * 4) + 2;
            for (var s = 0; s < len; s++) {
                var nx = cx + dir.dx, ny = cy + dir.dy;
                if (nx > 0 && nx < this.size - 1 && ny > 0 && ny < this.size - 1 && this.grid[ny][nx] === 0) {
                    this.grid[ny][nx] = 1;
                    cx = nx; cy = ny;
                } else break;
            }
        }
    };

    MazeGame.prototype.findPath = function(start, end) {
        var queue = [start];
        var visited = {};
        var parentMap = {};
        visited[start.x + ',' + start.y] = true;
        var dirs = [{dx:0,dy:-1},{dx:0,dy:1},{dx:-1,dy:0},{dx:1,dy:0}];
        while (queue.length > 0) {
            var current = queue.shift();
            if (current.x === end.x && current.y === end.y) {
                var path = [];
                var temp = current;
                while (temp) {
                    path.push(temp);
                    temp = parentMap[temp.x + ',' + temp.y];
                }
                return path.reverse();
            }
            for (var d = 0; d < dirs.length; d++) {
                var nx = current.x + dirs[d].dx;
                var ny = current.y + dirs[d].dy;
                var key = nx + ',' + ny;
                if (nx >= 0 && nx < this.size && ny >= 0 && ny < this.size && this.grid[ny][nx] === 1 && !visited[key]) {
                    visited[key] = true;
                    queue.push({x: nx, y: ny});
                    parentMap[key] = current;
                }
            }
        }
        return [];
    };

    MazeGame.prototype.findBranches = function(solutionPath) {
        var solutionSet = {};
        var i, p;
        for (i = 0; i < solutionPath.length; i++) {
            p = solutionPath[i];
            solutionSet[p.x + ',' + p.y] = true;
        }
        var branches = [];
        var visited = {};
        var neighbors = [{dx:1,dy:0},{dx:-1,dy:0},{dx:0,dy:1},{dx:0,dy:-1}];
        for (i = 0; i < solutionPath.length; i++) {
            p = solutionPath[i];
            for (var n = 0; n < neighbors.length; n++) {
                var nx = p.x + neighbors[n].dx;
                var ny = p.y + neighbors[n].dy;
                var key = nx + ',' + ny;
                if (nx >= 0 && nx < this.size && ny >= 0 && ny < this.size && this.grid[ny][nx] === 1 && !solutionSet[key] && !visited[key]) {
                    var branch = [];
                    var stack = [{x: nx, y: ny}];
                    visited[key] = true;
                    while (stack.length > 0) {
                        var curr = stack.pop();
                        branch.push(curr);
                        for (var bn = 0; bn < neighbors.length; bn++) {
                            var bnx = curr.x + neighbors[bn].dx;
                            var bny = curr.y + neighbors[bn].dy;
                            var bnKey = bnx + ',' + bny;
                            if (bnx >= 0 && bnx < this.size && bny >= 0 && bny < this.size && this.grid[bny][bnx] === 1 && !solutionSet[bnKey] && !visited[bnKey]) {
                                visited[bnKey] = true;
                                stack.push({x: bnx, y: bny});
                            }
                        }
                    }
                    if (branch.length > 0) branches.push(branch);
                }
            }
        }
        branches.sort(function(a, b) { return b.length - a.length; });
        return branches;
    };

    MazeGame.prototype.placeWords = function(solutionPath, branches) {
        var y, x;
        this.charGrid = [];
        this.solutionWordCells = {};
        for (y = 0; y < this.size; y++) {
            this.charGrid[y] = [];
            for (x = 0; x < this.size; x++) this.charGrid[y][x] = '';
        }
        var pathLength = solutionPath.length;
        var wordLength = this.solutionWord.length;
        var startIndex = Math.max(0, Math.floor((pathLength - wordLength) / 2));
        for (var i = 0; i < pathLength; i++) {
            var pos = solutionPath[i];
            if (i >= startIndex && i < startIndex + wordLength) {
                this.charGrid[pos.y][pos.x] = this.solutionWord[i - startIndex];
                this.solutionWordCells[pos.x + ',' + pos.y] = i - startIndex;
            }
        }
        var letters = 'BCDFGHJKLMNPQRSTVWXYZÄÖÜ';
        for (y = 0; y < this.size; y++) {
            for (x = 0; x < this.size; x++) {
                if (this.grid[y][x] === 1 && this.charGrid[y][x] === '') {
                    this.charGrid[y][x] = letters[Math.floor(Math.random() * letters.length)];
                }
            }
        }
        for (var w = 0; w < this.wrongWords.length && w < branches.length; w++) {
            var word = this.wrongWords[w];
            var branch = branches[w];
            for (var j = 0; j < branch.length && j < word.length; j++) {
                this.charGrid[branch[j].y][branch[j].x] = word[j];
            }
        }
    };

    MazeGame.prototype.render = function() {
        this.gridElement.innerHTML = '';
        for (var y = 0; y < this.size; y++) {
            for (var x = 0; x < this.size; x++) {
                var cell = document.createElement('div');
                cell.className = 'cell';
                if (this.grid[y][x] === 0) {
                    cell.className += ' wall';
                } else {
                    cell.textContent = this.charGrid[y][x];
                    if (x === 0 && y === 0) cell.className += ' start';
                    if (x === this.goalPos.x && y === this.goalPos.y) cell.className += ' goal';
                    if (x === this.playerPos.x && y === this.playerPos.y) {
                        cell.className += ' player';
                    }
                }
                this.gridElement.appendChild(cell);
            }
        }

        if (this.collectedLetters.length > 0 && !this.isGameOver) {
            var cells = this.gridElement.children;
            var trailLen = this.moveTrail.length;
            for (var c = 0; c < this.collectedLetters.length; c++) {
                var trailIdx = trailLen - 2 - c;
                if (trailIdx >= 0 && trailIdx < trailLen) {
                    var pos = this.moveTrail[trailIdx];
                    var cellIdx = pos.y * this.size + pos.x;
                    if (cells[cellIdx] && !cells[cellIdx].classList.contains('player')) {
                        cells[cellIdx].textContent = this.collectedLetters[c].char;
                        cells[cellIdx].style.color = '#c0392b';
                        cells[cellIdx].style.fontWeight = 'bold';
                        cells[cellIdx].style.textShadow = '0 0 3px rgba(192,57,43,0.5)';
                    }
                }
            }
        }
    };

    MazeGame.prototype.move = function(dx, dy) {
        if (this.isGameOver) return;
        var newX = this.playerPos.x + dx;
        var newY = this.playerPos.y + dy;
        if (newX >= 0 && newX < this.size && newY >= 0 && newY < this.size && this.grid[newY][newX] === 1) {
            var key = newX + ',' + newY;
            this.playerPos.x = newX;
            this.playerPos.y = newY;
            this.moveTrail.push({x: newX, y: newY});

            if (this.solutionWordCells[key] !== undefined && !this.visitedSolutionSet[key]) {
                this.visitedSolutionSet[key] = this.solutionWordCells[key];
                this.collectedLetters.push({x: newX, y: newY, char: this.charGrid[newY][newX], idx: this.solutionWordCells[key]});
                this.charGrid[newY][newX] = '';
                this.updateSolutionTracker();
            } else if (!this.solutionWordCells[key] && this.charGrid[newY][newX] !== '') {
                this.clearedCells[key] = this.charGrid[newY][newX];
                this.charGrid[newY][newX] = '';
            }

            this.isFirstMove = false;
            this.render();
            this.saveState();
            this.checkWin();
        }
    };

    MazeGame.prototype.updateSolutionTracker = function() {
        var tracker = document.getElementById('mazeword-solution-letters');
        if (!tracker) return;
        tracker.innerHTML = '';
        var collected = {};
        for (var c = 0; c < this.collectedLetters.length; c++) {
            collected[this.collectedLetters[c].idx] = true;
        }
        for (var i = 0; i < this.solutionWord.length; i++) {
            var span = document.createElement('span');
            span.className = 'mazeword-tracker-letter';
            span.textContent = this.solutionWord[i];
            if (collected[i]) span.className += ' visited';
            tracker.appendChild(span);
        }
    };

    MazeGame.prototype.saveState = function() {
        var self = this;
        var visited = [];
        for (var k in this.visitedCellsSet) visited.push(k);
        var solution = [];
        for (var sk in this.visitedSolutionSet) solution.push(sk);
        var cleared = [];
        for (var ck in this.clearedCells) cleared.push(ck);
        var collected = [];
        for (var ci = 0; ci < this.collectedLetters.length; ci++) {
            collected.push({x: this.collectedLetters[ci].x, y: this.collectedLetters[ci].y, char: this.collectedLetters[ci].char, idx: this.collectedLetters[ci].idx});
        }
        var trail = [];
        for (var ti = 0; ti < this.moveTrail.length; ti++) {
            trail.push({x: this.moveTrail[ti].x, y: this.moveTrail[ti].y});
        }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/mod/mazeword/view.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('action=save_state&id=' + self.cmid + '&sesskey=' + encodeURIComponent(self.sesskey) + '&playerx=' + self.playerPos.x + '&playery=' + self.playerPos.y + '&visitedcells=' + encodeURIComponent(JSON.stringify(visited)) + '&solutionvisited=' + encodeURIComponent(JSON.stringify(solution)) + '&completed=' + (self.isGameOver ? 1 : 0) + '&clearedcells=' + encodeURIComponent(JSON.stringify(cleared)) + '&collectedletters=' + encodeURIComponent(JSON.stringify(collected)) + '&movetrail=' + encodeURIComponent(JSON.stringify(trail)));
    };

    MazeGame.prototype.resetGame = function() {
        var self = this;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/mod/mazeword/view.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                self.visitedSolutionSet = {};
                self.visitedCellsSet = {};
                self.collectedLetters = [];
                self.clearedCells = {};
                self.moveTrail = [{x: 0, y: 0}];
                self.playerPos = {x: 0, y: 0};
                self.isGameOver = false;
                self.isFirstMove = true;
                self.particles = [];
                self.initGame();
            }
        };
        xhr.send('action=reset&id=' + self.cmid + '&sesskey=' + encodeURIComponent(self.sesskey));
    };

    MazeGame.prototype.checkWin = function() {
        if (this.playerPos.x === this.goalPos.x && this.playerPos.y === this.goalPos.y) {
            var allCollected = this.collectedLetters.length === this.solutionWord.length;
            if (allCollected) {
                this.isGameOver = true;
                this.statusElement.textContent = 'Gewonnen! Das Wort war: ' + this.solutionWord;
                this.statusElement.style.color = '#2ecc71';
                this.saveState();
                this.startFirework();
            } else {
                this.statusElement.textContent = 'Noch nicht geschafft! Sammle zuerst alle Buchstaben des Lösungswortes.';
                this.statusElement.style.color = '#e74c3c';
            }
        }
    };

    MazeGame.prototype.startFirework = function() {
        if (!this.canvas || !this.ctx) return;
        var rect = this.gridElement.getBoundingClientRect();
        this.canvas.width = rect.width;
        this.canvas.height = rect.height;
        var cellW = this.canvas.width / this.size;
        var cellH = this.canvas.height / this.size;
        var cx = this.goalPos.x * cellW + cellW / 2;
        var cy = this.goalPos.y * cellH + cellH / 2;
        for (var i = 0; i < 150; i++) {
            this.particles.push(this.createParticle(cx, cy));
        }
        this.animateFirework();
    };

    MazeGame.prototype.createParticle = function(x, y) {
        var angle = Math.random() * Math.PI * 2;
        var speed = Math.random() * 6 + 2;
        var hue = Math.floor(Math.random() * 360);
        return {x: x, y: y, vx: Math.cos(angle) * speed, vy: Math.sin(angle) * speed, alpha: 1, color: 'hsl(' + hue + ', 100%, 60%)', radius: Math.random() * 3 + 2};
    };

    MazeGame.prototype.animateFirework = function() {
        if (this.particles.length === 0 || !this.ctx) return;
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        for (var i = this.particles.length - 1; i >= 0; i--) {
            var p = this.particles[i];
            p.x += p.vx;
            p.y += p.vy;
            p.vy += 0.1;
            p.alpha -= 0.015;
            if (p.alpha <= 0) {
                this.particles.splice(i, 1);
            } else {
                this.ctx.globalAlpha = p.alpha;
                this.ctx.fillStyle = p.color;
                this.ctx.beginPath();
                this.ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                this.ctx.fill();
            }
        }
        if (this.particles.length > 0) {
            var self = this;
            requestAnimationFrame(function() { self.animateFirework(); });
        }
    };

    game = new MazeGame(config, savedX, savedY, JSON.parse(visitedJson||'[]'), JSON.parse(solutionJson||'[]'), JSON.parse(clearedJson||'[]'), JSON.parse(collectedJson||'[]'), JSON.parse(trailJson||'[]'), !!completed);

    document.addEventListener('keydown', function(e) {
        if (!game || game.isGameOver) return;
        switch(e.key) {
            case 'ArrowUp': case 'w': case 'W': e.preventDefault(); game.move(0, -1); break;
            case 'ArrowDown': case 's': case 'S': e.preventDefault(); game.move(0, 1); break;
            case 'ArrowLeft': case 'a': case 'A': e.preventDefault(); game.move(-1, 0); break;
            case 'ArrowRight': case 'd': case 'D': e.preventDefault(); game.move(1, 0); break;
        }
    });

    document.querySelector('.mazeword-btn-up').addEventListener('click', function(e) { e.preventDefault(); if (!game.isGameOver) game.move(0, -1); });
    document.querySelector('.mazeword-btn-left').addEventListener('click', function(e) { e.preventDefault(); if (!game.isGameOver) game.move(-1, 0); });
    document.querySelector('.mazeword-btn-down').addEventListener('click', function(e) { e.preventDefault(); if (!game.isGameOver) game.move(0, 1); });
    document.querySelector('.mazeword-btn-right').addEventListener('click', function(e) { e.preventDefault(); if (!game.isGameOver) game.move(1, 0); });
    document.getElementById('mazeword-reset-btn').addEventListener('click', function(e) { e.preventDefault(); game.resetGame(); });

})();
</script>
JS;

        return $output;
    }
}
