
<style>
body, html {
	height: 100%;
	margin: 0;

	font-family: sans-serif;
	font-size: 11px;
}

body {
	background-image: url('./live_scoreboard.min.png');
	background-repeat: no-repeat;
	background-size: cover;
	background-position: center;

	user-select: none;
}

.clcmd_say {
	position: fixed;
	width: 100%;

	top: 2px;
	left: 7px;

	color: #ffb41e;
	font-size: 14px;
}

#input_say {
	background-color: transparent;
	color: transparent;
	text-shadow: 0 0 0 #ffb41e;
	border: none;
	width: 95%;
}

input:focus {
	outline: none;
}

.scoreboard {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);

	min-height: 540px;
	min-width: 660px;

	border-color: rgba(92, 72, 13, 1.0);
	border-width: 1px;
	border-style: solid;

	background: rgba(0, 0, 0, 0.5);

	z-index: 1;
}

.say {
	position: fixed;
	width: 100%;

	bottom: 80px;
	left: 12px;
	
	height: 80px;

	font-size: 14px;
}

.table-default, .table-t, .table-ct, .table-sp {
	width: 94%;
}

.mt5 { margin-top: 5px; }

.table-default tr th, .def { color: #ffb41e; }
.table-default tr th hr { background-color: rgba(92, 72, 13, 1.0); }

.table-t tr th, .t { color: #ff3333; }
.table-t tr th hr { background-color: #ff3333; }

.table-ct tr th, .ct { color: lightskyblue; }
.table-ct tr th hr { background-color: lightskyblue; }
.table-ct tr th { padding-right: 5px; }

.table-sp tr th, .sp { color: #ccc; }

.line {
	position: absolute;
	width: 100%;
}

.line hr {
	border: none;
	height: 1px;

	margin-top: -2px;
	margin-bottom: 0px;
	margin-right: 7px;
	margin-left: 1px;
}

.name { padding-left: 7px; }
.status { width: 6%; }
.action { width: 6%; }
.score { width: 8%; }
.deaths { width: 10%; }
.latency { width: 12%; }

.name, .left { text-align: left; }
.status { text-align: left; }
.action, .score, .deaths, .latency { text-align: right; }



</style>


<div class="clcmd_say" style="display: none;">
	<data>say:</data> <input id="input_say">
</div>

<div class="scoreboard">
	<table class="table-default">
		<tr>
			<th class="left"></th>
			<th class="status"></th>
			<th class="action"></th>
			<th class="score">Score</th>
			<th class="deaths">Deaths</th>
			<th class="latency">Latency</th>
		</tr>

		<tr>
			<th class="line" colspan="4"><hr></th>
		</tr>
	</table>
		
	<table class="table-t mt5" style="display: none;">
		<tr>
			<th class="left">Terrorists&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;&nbsp;<data>?</data> player<data></data></th>
			<th class="status"></th>
			<th class="action"></th>
			<th class="score"><data>?</data></th>
			<th class="deaths"></th>
			<th class="latency"></th>
		</tr>

		<tr>
			<th class="line" colspan="4"><hr></th>
		</tr>

		<tfoot><!--players t--></tfoot>
	</table>
		
	<table class="table-ct mt5" style="display: none;">
		<tr>
			<th class="left">Counter-Terrorists&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;&nbsp;<data>?</data> player<data></data></th>
			<th class="status"></th>
			<th class="action"></th>
			<th class="score"><data>?</data></th>
			<th class="deaths"></th>
			<th class="latency"></th>
		</tr>

		<tr>
			<th class="line" colspan="4"><hr></th>
		</tr>

		<tfoot><!--players ct--></tfoot>
	</table>

	<table class="table-default mt5" style="display: none;">
		<tr>
			<th class="left">Spectators</th>
		</tr>

		<tr>
			<th class="line"><hr></th>
		</tr>

		<tfoot class="table-sp"><!--players sp--></tfoot>
	</table>
</div>

<div class="say def"></div>

<script src="live_scoreboard.js"></script>


<script>

let ws, attemps, conn = window.location.search.replace('?', '').split(':');

let CMD_LEN = {
	's': 5,
	'p': 9,
	'say': 2
};

let server = {};
let players = {};
let messages = [];

//say
let clcmd_say = qSel('.clcmd_say');
let input_say = qSel('.clcmd_say input');
let say = qSel('.say');
let timeout_say = -1;

//server
let count_t = qSelAll('.table-t tr th.left data');
let count_ct = qSelAll('.table-ct tr th.left data');

let score_t = qSel('.table-t tr th.score data');
let score_ct = qSel('.table-ct tr th.score data');

let latency_t = qSel('.table-t tr th.latency');
let latency_ct = qSel('.table-ct tr th.latency');

let hostname = qSel('.table-default tr th.left');

//players
let table_t = qSel('.table-t');
let players_t = qSel('.table-t tfoot');

let table_ct = qSel('.table-ct');
let players_ct = qSel('.table-ct tfoot');

let table_sp = qSelAll('.table-default')[1];
let players_sp = qSel('tfoot.table-sp');

//
if(conn.length === 2) {
	conn[1] = 65535 - parseInt(conn[1]);

	init();
}

//detect key to open say and send
window.addEventListener('keypress', function(e) {
	//only check for open say if it's not open
	if(clcmd_say.style.display) {
		//is y?
		if(e.key === 'y' || e.which === 121 || e.charCode === 121) {
			console.log('open say');

			if(ws.readyState === 1) {
				input_say.value = '';
				clcmd_say.style.display = '';
			}
		}
	}

	//only check enter when say is open
	if(!clcmd_say.style.display) {
		//is enter?
		if(e.key === 'Enter' || e.which === 13 || e.charCode === 13) {
			let value = input_say.value.trim();

			//socket open and there is value
			if(ws.readyState === 1 && value) {
				ws.send(value);

				/*messages.push({ id: false, text: value, expire: new Date().getTime() + 5000 });

				update_say();*/
			}

			input_say.blur();
			clcmd_say.style.display = 'none';
			input_say.value = '';
		}
	}
});

window.addEventListener('keyup', function(e) {
	if(e.key === 'y' || e.which === 121 || e.charCode === 121) {
		input_say.focus();
	}
});

function init() {
	ws = new WebSocket('ws://' + conn[0] + ':' + conn[1]);

	ws.onclose = function(e) {
		if(attemps++ === 10) {
			players = {};
			update_players();
		}

		setTimeout(init, 1000);
	};

	ws.onmessage = function(e) {
		//attemps = 0;

		if(e.data === 'end') {
			players = {};

			return hostname.innerHTML = 'Changing map';
		}

		let cmd = e.data.split(':')[0];

		//parse data, for example: 'p: 1 0 0 0 0 16 1 name with spaces'
		let d = e.data.substr(e.data.indexOf(':') + 2).split(' '); //[1, 0, 0, 0, 0, 16, 1, 'name', 'with', 'spaces']

		d = d.splice(0, CMD_LEN[cmd] - 1).concat(d.join(' ')) //[1, 0, 0, 0, 0, 16, 1, 'name with spaces']
	
		//data values to int except name (last one)
		for(let i = 0; i < d.length - 1; i++) {
			d[i] = parseInt(d[i]);
		}

		//check for server update
		if(cmd === 's') {
			server = { t: d[0], ct: d[1], score_t: d[2], score_ct: d[3], hostname: d[4] };
			update_server();
			return;
		}
		else if(cmd === 'p') {
			//was player so
			if(d[1] === -1) { //disconnect -> remove
				players[d[0]] = false;
			}
			else { //exists or not -> add/update
				players[d[0]] = {
					id: d[0],
					alive: d[1] === 1,
					vip: d[2] === 1,
					c4: d[3] === 1,
					score: d[4],
					deaths: d[5],
					latency: d[6],
					team: d[7],
					name: d[8]
				};
			}

			update_players();
		}
		else {
			let pl = players[d[0]];

			messages.push({ id: d[0], text: d[1], expire: new Date().getTime() + 5000, alive: pl ? pl.alive : false, team: pl ? pl.team : 0 });

			update_say();
		}
	};
}

function update_server() {
	count_t[0].innerHTML = server.t;
	count_t[1].innerHTML = server.t === 1 ? '' : 's';
	count_ct[0].innerHTML = server.ct;
	count_ct[1].innerHTML = server.ct === 1 ? '' : 's';

	score_t.innerHTML = server.score_t;
	score_ct.innerHTML = server.score_ct;

	hostname.innerHTML = server.hostname;
}

function update_players() {
	let sorted = [], team = ['', '', ''];

	for(let i in players) {
		players[i] && sorted.push(players[i]);
	}

	sorted.sort(function(a, b) {
		return b.score - a.score || a.deaths - b.deaths;
	});

	let latency = [0, 0];

	for(let i = 0; i < sorted.length; i++) {
		let d = sorted[i];

		//in team
		if(d.team) {
			team[d.team] += 
			'<tr>' +
				'<th class="name">' + encodeHTML(d.name) + '</th>' +
				'<th class="status">' + (d.alive ? (d.vip ? 'Vip' : (d.c4 ? 'Bomb' : '')) : 'Dead') + '</th>' +
				'<th class="action"></th>' +
				'<th class="score">' + d.score + '</th>' +
				'<th class="deaths">' + d.deaths + '</th>' +
				'<th class="latency">' + (d.latency ? d.latency : '') + '</th>' +
			'</tr>';

			latency[d.team === 1 ? 0 : 1] += d.latency;
		}
		//specs
		else {
			team[d.team] = '<tr><th class="name">' + encodeHTML(d.name) + '</th></tr>';
		}
	};

	latency_t.innerHTML = latency[0] ? Math.round(latency[0] / server.t) : '';
	latency_ct.innerHTML = latency[1] ? Math.round(latency[1] / server.ct) : '';

	players_t.innerHTML = team[1];
	table_t.style.display = team[1] ? '' : 'none';

	players_ct.innerHTML = team[2];
	table_ct.style.display = team[2] ? '' : 'none';

	players_sp.innerHTML = team[0];
	table_sp.style.display = team[0] ? '' : 'none';
}

function update_say() {
	let out = '';

	for(let i = 0; i < messages.length; i++) {
		//only 5 messages max and must be recents
		if(messages[i].id !== -1 && !players[messages[i].id] || messages.length > 5) {
			messages.splice(i--, 1);
			continue;
		}

		if(messages[i].expire < new Date().getTime()) {
			//only will remove one at time

			messages.splice(i--, 1);

			for(let i = 0; i < messages.length; i++) {
				messages[i].expire = new Date().getTime() + 5000;
			}
			
			continue;
		}

		let pl = players[messages[i].id];
		
		let extra = pl && !messages[i].alive ? (messages[i].team ? '*DEAD* ' : '*SPEC* ') : '';
		let team_color = pl && messages[i].team ? (messages[i].team === 1 ? 't' : 'ct') : 'sp';
		let name = pl ? pl.name : '[Live Web]';

		out += 
			'<div class="say-msg">' +
				extra +
				'<data class="' + team_color + '">' + encodeHTML(name) + '</data>' +
				' : ' + encodeHTML(messages[i].text) +
			'</div>';
	}

	say.innerHTML = out;

	//recall
	clearTimeout(timeout_say);
	timeout_say = setTimeout(update_say, 1000);
}

function qSel(arg) { return document.querySelector(arg); }
function qSelAll(arg) { return document.querySelectorAll(arg); }

function encodeHTML(str) {
	return str.replace(/[\u00A0-\u9999<>\&]/gim, function(i) {
		return '&#' + i.charCodeAt(0) + ';';
	});
}


</script>
