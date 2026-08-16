/**
 * Mazeword game - AMD module.
 *
 * @module mod_mazeword/mazeword
 */
define(['jquery'], function($) {
    'use strict';

    let game = null;

    /**
     * Initialize the maze game.
     *
     * @param {string} configJson - JSON configuration string.
     * @param {number} savedX - Saved player X position.
     * @param {number} savedY - Saved player Y position.
     * @param {string} visitedJson - JSON string of visited cells.
     * @param {string} solutionJson - JSON string of visited solution cells.
     * @param {boolean} completed - Whether the game was completed.
     */
    function init(configJson, savedX, savedY, visitedJson, solutionJson, completed) {
        const config = JSON.parse(configJson);
        const visitedCells = JSON.parse(visitedJson || '[]');
        const solutionVisited = JSON.parse(solutionJson || '[]');

        game = new MazeGame(config, savedX, savedY, visitedCells, solutionVisited, completed);
    }

    /**
     * MazeGame class - handles all game logic.
     */
    class MazeGame {
        constructor(config, savedX, savedY, visitedCells, solutionVisited, completed) {
            this.config = config;
            this.size = config.mazesize;
            this.solutionWord = config.solutionword;
            this.wrongWords = [config.wrongword1, config.wrongword2, config.wrongword3];
            this.instanceid = config.instanceid;
            this.cmid = config.cmid;
            this.sesskey = config.sesskey;

            this.grid = [];
            this.charGrid = [];
            this.solutionWordCells = new Set();
            this.visitedSolutionSet = new Set(solutionVisited);
            this.visitedCellsSet = new Set(visitedCells);

            this.playerPos = { x: savedX, y: savedY };
            this.goalPos = { x: this.size - 2, y: this.size - 2 };
            this.isGameOver = completed;
            this.isFirstMove = !completed && savedX === 0 && savedY === 0;

            this.gridElement = $('#mazeword-grid');
            this.statusElement = $('#mazeword-status');
            this.canvas = document.getElementById('mazeword-firework-canvas');
            this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
            this.particles = [];

            this.setupEventListeners();
            this.initGame();
        }

        setupEventListeners() {
            const self = this;

            // Keyboard controls.
            $(document).on('keydown.mazeword', function(e) {
                if (self.isGameOver) return;
                switch(e.key) {
                    case 'ArrowUp': case 'w': case 'W':
                        e.preventDefault();
                        self.move(0, -1);
                        break;
                    case 'ArrowDown': case 's': case 'S':
                        e.preventDefault();
                        self.move(0, 1);
                        break;
                    case 'ArrowLeft': case 'a': case 'A':
                        e.preventDefault();
                        self.move(-1, 0);
                        break;
                    case 'ArrowRight': case 'd': case 'D':
                        e.preventDefault();
                        self.move(1, 0);
                        break;
                }
            });

            // Button controls.
            $(document).on('click', '.mazeword-btn[data-dir]', function(e) {
                e.preventDefault();
                if (self.isGameOver) return;
                const dir = $(this).data('dir');
                switch(dir) {
                    case 'up': self.move(0, -1); break;
                    case 'down': self.move(0, 1); break;
                    case 'left': self.move(-1, 0); break;
                    case 'right': self.move(1, 0); break;
                }
            });

            // Reset button.
            $(document).on('click', '#mazeword-reset-btn', function(e) {
                e.preventDefault();
                self.resetGame();
            });

            // Save state before page unload.
            $(window).on('beforeunload.mazeword', function() {
                self.saveState();
            });
        }

        initGame() {
            this.generateMaze();
            this.addExtraDeadEnds();

            // Find solution path.
            const solutionPath = this.findPath(this.playerPos, this.goalPos);

            // Find branches (dead ends).
            const branches = this.findBranches(solutionPath);

            // Place words.
            this.placeWords(solutionPath, branches);

            // Restore visited cells for non-solution cells.
            this.restoreVisitedCells();

            // Render.
            this.render();
            this.updateSolutionTracker();

            if (this.isGameOver) {
                this.statusElement.text('Gewonnen! Das Wort war: ' + this.solutionWord);
                this.statusElement.css('color', '#2ecc71');
                this.startFirework();
            } else if (this.isFirstMove) {
                this.statusElement.text('Spiel gestartet! Finde den Weg des Lösungswortes.');
                this.statusElement.css('color', '#f1c40f');
            } else {
                this.statusElement.text('Spiel fortgesetzt.');
                this.statusElement.css('color', '#f1c40f');
            }
        }

        resetGame() {
            const self = this;
            $.ajax({
                url: '/mod/mazeword/view.php',
                type: 'POST',
                data: {
                    action: 'reset',
                    id: self.cmid,
                    sesskey: self.sesskey
                },
                dataType: 'json',
                success: function() {
                    self.visitedSolutionSet = new Set();
                    self.visitedCellsSet = new Set();
                    self.playerPos = { x: 0, y: 0 };
                    self.isGameOver = false;
                    self.isFirstMove = true;
                    self.particles = [];
                    self.initGame();
                }
            });
        }

        generateMaze() {
            this.grid = Array(this.size).fill(null).map(() => Array(this.size).fill(0));
            const stack = [];
            const startNode = { x: 0, y: 0 };
            this.grid[startNode.y][startNode.x] = 1;
            stack.push(startNode);

            const directions = [
                { dx: 0, dy: -2 }, { dx: 0, dy: 2 },
                { dx: -2, dy: 0 }, { dx: 2, dy: 0 }
            ];

            while (stack.length > 0) {
                const current = stack[stack.length - 1];
                const shuffledDirs = [...directions].sort(() => Math.random() - 0.5);
                let moved = false;

                for (const dir of shuffledDirs) {
                    const nx = current.x + dir.dx;
                    const ny = current.y + dir.dy;

                    if (nx >= 0 && nx < this.size && ny >= 0 && ny < this.size && this.grid[ny][nx] === 0) {
                        this.grid[current.y + dir.dy / 2][current.x + dir.dx / 2] = 1;
                        this.grid[ny][nx] = 1;
                        stack.push({ x: nx, y: ny });
                        moved = true;
                        break;
                    }
                }
                if (!moved) stack.pop();
            }
        }

        addExtraDeadEnds() {
            let pathCells = [];
            for (let y = 0; y < this.size; y++) {
                for (let x = 0; x < this.size; x++) {
                    if (this.grid[y][x] === 1) {
                        pathCells.push({ x, y });
                    }
                }
            }
            pathCells.sort(() => Math.random() - 0.5);
            for (let i = 0; i < 15; i++) {
                if (i >= pathCells.length) break;
                const start = pathCells[i];
                const dirs = [
                    { dx: 0, dy: 1 }, { dx: 0, dy: -1 },
                    { dx: 1, dy: 0 }, { dx: -1, dy: 0 }
                ];
                const dir = dirs[Math.floor(Math.random() * dirs.length)];
                let cx = start.x;
                let cy = start.y;
                const len = Math.floor(Math.random() * 4) + 2;
                for (let s = 0; s < len; s++) {
                    const nx = cx + dir.dx;
                    const ny = cy + dir.dy;
                    if (nx > 0 && nx < this.size - 1 && ny > 0 && ny < this.size - 1 && this.grid[ny][nx] === 0) {
                        this.grid[ny][nx] = 1;
                        cx = nx;
                        cy = ny;
                    } else {
                        break;
                    }
                }
            }
        }

        findPath(start, end) {
            const queue = [start];
            const visited = new Set();
            const parentMap = new Map();
            visited.add(`${start.x},${start.y}`);

            const directions = [
                { dx: 0, dy: -1 }, { dx: 0, dy: 1 },
                { dx: -1, dy: 0 }, { dx: 1, dy: 0 }
            ];

            while (queue.length > 0) {
                const current = queue.shift();
                if (current.x === end.x && current.y === end.y) {
                    const path = [];
                    let temp = current;
                    while (temp) {
                        path.push(temp);
                        temp = parentMap.get(`${temp.x},${temp.y}`);
                    }
                    return path.reverse();
                }

                for (const d of directions) {
                    const nx = current.x + d.dx;
                    const ny = current.y + d.dy;
                    const key = `${nx},${ny}`;

                    if (nx >= 0 && nx < this.size && ny >= 0 && ny < this.size &&
                        this.grid[ny][nx] === 1 && !visited.has(key)) {
                        visited.add(key);
                        queue.push({ x: nx, y: ny });
                        parentMap.set(key, current);
                    }
                }
            }
            return [];
        }

        findBranches(solutionPath) {
            const solutionSet = new Set(solutionPath.map(p => `${p.x},${p.y}`));
            const branches = [];
            const visited = new Set();

            for (const p of solutionPath) {
                const neighbors = [
                    { x: p.x + 1, y: p.y }, { x: p.x - 1, y: p.y },
                    { x: p.x, y: p.y + 1 }, { x: p.x, y: p.y - 1 }
                ];

                for (const n of neighbors) {
                    const key = `${n.x},${n.y}`;
                    if (n.x >= 0 && n.x < this.size && n.y >= 0 && n.y < this.size &&
                        this.grid[n.y][n.x] === 1 && !solutionSet.has(key) && !visited.has(key)) {

                        const branch = [];
                        const stack = [n];
                        visited.add(key);

                        while (stack.length > 0) {
                            const curr = stack.pop();
                            branch.push(curr);

                            const bNeigh = [
                                { x: curr.x + 1, y: curr.y }, { x: curr.x - 1, y: curr.y },
                                { x: curr.x, y: curr.y + 1 }, { x: curr.x, y: curr.y - 1 }
                            ];
                            for (const bn of bNeigh) {
                                const bnKey = `${bn.x},${bn.y}`;
                                if (bn.x >= 0 && bn.x < this.size && bn.y >= 0 && bn.y < this.size &&
                                    this.grid[bn.y][bn.x] === 1 && !solutionSet.has(bnKey) && !visited.has(bnKey)) {
                                    visited.add(bnKey);
                                    stack.push(bn);
                                }
                            }
                        }
                        if (branch.length > 0) branches.push(branch);
                    }
                }
            }
            return branches;
        }

        placeWords(solutionPath, branches) {
            // Initialize empty char grid.
            this.charGrid = Array(this.size).fill(null).map(() => Array(this.size).fill(''));
            this.solutionWordCells = new Set();

            // Place solution word centered on path.
            const pathLength = solutionPath.length;
            const wordLength = this.solutionWord.length;
            const startIndex = Math.max(0, Math.floor((pathLength - wordLength) / 2));

            for (let i = 0; i < pathLength; i++) {
                const pos = solutionPath[i];
                if (i >= startIndex && i < startIndex + wordLength) {
                    const charIndex = i - startIndex;
                    this.charGrid[pos.y][pos.x] = this.solutionWord[charIndex];
                    this.solutionWordCells.add(`${pos.x},${pos.y}`);
                } else {
                    this.charGrid[pos.y][pos.x] = '';
                }
            }

            // Fill empty path cells with random letters.
            const defaultRandomLetters = 'BCDFGHJKLMNPQRSTVWXYZ';
            for (let y = 0; y < this.size; y++) {
                for (let x = 0; x < this.size; x++) {
                    if (this.grid[y][x] === 1 && this.charGrid[y][x] === '') {
                        this.charGrid[y][x] = defaultRandomLetters[Math.floor(Math.random() * defaultRandomLetters.length)];
                    }
                }
            }

            // Place wrong words in branches.
            branches.sort((a, b) => b.length - a.length);
            for (let i = 0; i < this.wrongWords.length; i++) {
                if (i >= branches.length) break;
                const word = this.wrongWords[i];
                const branch = branches[i];
                for (let j = 0; j < branch.length && j < word.length; j++) {
                    const pos = branch[j];
                    this.charGrid[pos.y][pos.x] = word[j];
                }
            }
        }

        restoreVisitedCells() {
            // Restore non-solution visited cells.
            for (const key of this.visitedCellsSet) {
                const [x, y] = key.split(',').map(Number);
                if (!this.solutionWordCells.has(key)) {
                    // These cells were visited but not solution cells.
                }
            }
        }

        render() {
            this.gridElement.empty();

            for (let y = 0; y < this.size; y++) {
                for (let x = 0; x < this.size; x++) {
                    const cell = $('<div>').addClass('cell');

                    if (this.grid[y][x] === 0) {
                        cell.addClass('wall');
                    } else {
                        const char = this.charGrid[y][x];
                        cell.text(char);

                        if (x === 0 && y === 0) cell.addClass('start');
                        if (x === this.goalPos.x && y === this.goalPos.y) cell.addClass('goal');

                        if (this.visitedSolutionSet.has(`${x},${y}`)) {
                            cell.css('color', '#c0392b');
                        } else if (this.solutionWordCells.has(`${x},${y}`)) {
                            cell.css('color', '#2c3e50');
                        }

                        if (x === this.playerPos.x && y === this.playerPos.y) {
                            cell.addClass('player');
                        }
                    }

                    this.gridElement.append(cell);
                }
            }
        }

        move(dx, dy) {
            if (this.isGameOver) return;

            const newX = this.playerPos.x + dx;
            const newY = this.playerPos.y + dy;

            if (newX >= 0 && newX < this.size && newY >= 0 && newY < this.size) {
                if (this.grid[newY][newX] === 1) {
                    this.playerPos.x = newX;
                    this.playerPos.y = newY;

                    // Track visited cells.
                    const key = `${newX},${newY}`;
                    this.visitedCellsSet.add(key);

                    if (this.solutionWordCells.has(key)) {
                        this.visitedSolutionSet.add(key);
                        this.updateSolutionTracker();
                    }

                    this.isFirstMove = false;
                    this.render();
                    this.saveState();
                    this.checkWin();
                }
            }
        }

        updateSolutionTracker() {
            const tracker = $('#mazeword-solution-letters');
            if (!tracker.length) return;

            tracker.empty();
            for (let i = 0; i < this.solutionWord.length; i++) {
                const key = Array.from(this.solutionWordCells).find(k => {
                    const [x, y] = k.split(',').map(Number);
                    const idx = Array.from(this.solutionWordCells).indexOf(k);
                    return idx === i;
                });

                // Find which solution cell has this letter index.
                let isVisited = false;
                let letter = this.solutionWord[i];
                let cellIndex = 0;

                for (const cellKey of this.solutionWordCells) {
                    if (cellIndex === i) {
                        isVisited = this.visitedSolutionSet.has(cellKey);
                        break;
                    }
                    cellIndex++;
                }

                const span = $('<span>').addClass('mazeword-tracker-letter');
                span.text(letter);
                if (isVisited) {
                    span.addClass('visited');
                }
                tracker.append(span);
            }
        }

        saveState() {
            const visitedArray = Array.from(this.visitedCellsSet);
            const solutionArray = Array.from(this.visitedSolutionSet);

            $.ajax({
                url: '/mod/mazeword/view.php',
                type: 'POST',
                data: {
                    action: 'save_state',
                    id: this.cmid,
                    sesskey: this.sesskey,
                    playerx: this.playerPos.x,
                    playery: this.playerPos.y,
                    visitedcells: JSON.stringify(visitedArray),
                    solutionvisited: JSON.stringify(solutionArray),
                    completed: this.isGameOver ? 1 : 0
                },
                dataType: 'json',
                silent: true
            });
        }

        checkWin() {
            if (this.playerPos.x === this.goalPos.x && this.playerPos.y === this.goalPos.y) {
                this.isGameOver = true;
                this.statusElement.text('Gewonnen! Das Wort war: ' + this.solutionWord);
                this.statusElement.css('color', '#2ecc71');
                this.saveState();
                this.startFirework();
            }
        }

        startFirework() {
            if (!this.canvas || !this.ctx) return;

            const rect = this.gridElement[0].getBoundingClientRect();
            this.canvas.width = rect.width;
            this.canvas.height = rect.height;

            const cellW = this.canvas.width / this.size;
            const cellH = this.canvas.height / this.size;
            const cx = this.goalPos.x * cellW + cellW / 2;
            const cy = this.goalPos.y * cellH + cellH / 2;

            for (let i = 0; i < 150; i++) {
                this.particles.push(this.createParticle(cx, cy));
            }

            this.animateFirework();
        }

        createParticle(x, y) {
            const angle = Math.random() * Math.PI * 2;
            const speed = Math.random() * 6 + 2;
            const hue = Math.floor(Math.random() * 360);

            return {
                x: x,
                y: y,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed,
                alpha: 1,
                color: `hsl(${hue}, 100%, 60%)`,
                radius: Math.random() * 3 + 2
            };
        }

        animateFirework() {
            if (this.particles.length === 0 || !this.ctx) return;

            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

            for (let i = this.particles.length - 1; i >= 0; i--) {
                const p = this.particles[i];

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
                const self = this;
                requestAnimationFrame(function() {
                    self.animateFirework();
                });
            }
        }
    }

    return {
        init: init
    };
});
