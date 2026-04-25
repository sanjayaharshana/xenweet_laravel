@extends('layouts.host')

@section('title', 'MySQL Management - Xenweet')

@section('content')
<div class="host-panel-scope managedb-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Manage DB</p>
            <h1>{{ $hosting->domain }}</h1>
            <p class="subtle">MySQL management page with create and listing tools.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    <section class="server-card managedb-prefix-card">
        <div class="managedb-prefix-card__icon"><i class="fa fa-shield"></i></div>
        <div>
            <p class="managedb-prefix-card__title">Prefix protection enabled</p>
            <p class="managedb-prefix-card__text">Every database and user will be created as <code>{{ $prefix }}_your_name</code>.</p>
        </div>
    </section>

    @if (session('success'))
        <div class="flash-success managedb-flash">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error managedb-flash">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($loadDbError)
        <div class="alert error managedb-flash">
            Could not load database list: {{ $loadDbError }}
        </div>
    @endif

    @if ($loadUsersError)
        <div class="alert error managedb-flash">
            Could not load MySQL user list (insufficient privileges for mysql.user): {{ $loadUsersError }}
        </div>
    @endif

    @if (!empty($loadGrantsError))
        <div class="alert error managedb-flash">
            Could not load MySQL access grants for flow diagram: {{ $loadGrantsError }}
        </div>
    @endif

    <nav class="managedb-tabs" aria-label="MySQL management sections">
        <a
            href="{{ route('hosts.db.manage.mysql', ['hosting' => $hosting, 'tab' => 'overview']) }}"
            class="managedb-tab {{ $activeTab === 'overview' ? 'is-active' : '' }}"
        >
            Access Flow Builder
        </a>
        <a
            href="{{ route('hosts.db.manage.mysql', ['hosting' => $hosting, 'tab' => 'users']) }}"
            class="managedb-tab {{ $activeTab === 'users' ? 'is-active' : '' }}"
        >
            DB Users Management
        </a>
        <a
            href="{{ route('hosts.db.manage.mysql', ['hosting' => $hosting, 'tab' => 'databases']) }}"
            class="managedb-tab {{ $activeTab === 'databases' ? 'is-active' : '' }}"
        >
            Database management
        </a>
    </nav>

    @if ($activeTab === 'overview')
        <section class="server-card managedb-card managedb-card--flow">
            <div class="managedb-card__head">
                <h2>Access Flow Builder</h2>
                <p>Drag nodes to arrange your map. Drag from a user connector to a database to visualize access links.</p>
            </div>

            <div class="managedb-flow-toolbar">
                <div class="managedb-flow-tools">
                    <button
                        type="button"
                        class="managedb-flow-tool-btn is-active"
                        data-flow-action="toggle-grid"
                        data-grid-enabled="1"
                        aria-label="Toggle grid"
                    >
                        <i class="fa fa-th" aria-hidden="true"></i>
                        <span class="managedb-flow-tooltip" role="tooltip">
                            <strong>Toggle grid</strong>
                            <span>Enable or disable background alignment grid for the diagram canvas.</span>
                        </span>
                    </button>
                    <button
                        type="button"
                        class="managedb-flow-tool-btn"
                        data-flow-action="open-add-user-modal"
                        aria-label="Create user node"
                    >
                        <i class="fa fa-user-plus" aria-hidden="true"></i>
                        <span class="managedb-flow-tooltip" role="tooltip">
                            <strong>Create user node</strong>
                            <span>Add a new MySQL user node with host prefix and password.</span>
                        </span>
                    </button>
                    <button
                        type="button"
                        class="managedb-flow-tool-btn"
                        data-flow-action="open-add-db-modal"
                        aria-label="Create database node"
                    >
                        <i class="fa fa-database" aria-hidden="true"></i>
                        <span class="managedb-flow-tooltip" role="tooltip">
                            <strong>Create database node</strong>
                            <span>Add a new MySQL database node using host-prefixed naming.</span>
                        </span>
                    </button>
                </div>
                <div class="managedb-flow-toolbar__group managedb-flow-toolbar__group--actions">
                    <button type="button" class="btn-secondary" data-flow-action="apply-access">Apply Access</button>
                </div>
            </div>

            <div
                id="managedb-flow-board"
                class="managedb-flow-board"
                data-users='@json($users)'
                data-user-passwords='@json($userPasswords)'
                data-databases='@json($databases)'
                data-grants='@json($grants)'
                data-mysql-info='@json($mysqlInfo)'
                data-adminer-open-url="{{ $adminerAutologinUrl ?? '' }}"
            >
                <svg id="managedb-flow-svg" class="managedb-flow-board__svg" aria-hidden="true"></svg>
                <div id="managedb-flow-context-menu" class="managedb-flow-context-menu" hidden>
                    <button type="button" data-menu-action="view-user-details" id="managedb-menu-user-details">View user details</button>
                    <button type="button" data-menu-action="open-in-adminer" id="managedb-menu-open-adminer">Open in Adminer</button>
                    <button type="button" data-menu-action="view-db-details" id="managedb-menu-db-details">Database info</button>
                    <button type="button" data-menu-action="delete-node">Delete node</button>
                    <button type="button" data-menu-action="remove-links">Remove links</button>
                </div>
            </div>
            <form id="managedb-apply-access-form" method="POST" action="{{ route('hosts.db.apply-access-graph', $hosting) }}" style="display:none;">
                @csrf
                <input type="hidden" name="graph_payload" id="managedb-graph-payload">
            </form>

            <p class="managedb-flow-help">
                Tip: drag nodes to reposition. Drag the user connector dot and drop it on a database node to connect.
            </p>
        </section>
        <dialog id="managedb-user-node-modal" class="managedb-modal">
            <form method="dialog" id="managedb-user-node-form" class="managedb-modal__form">
                <h3>Add User Node</h3>
                <p class="managedb-modal__desc">Create a user node for this host. The host prefix is locked and will be applied automatically.</p>
                <div class="managedb-modal__tip">
                    <strong>Tip:</strong> Use a strong password (8+ chars). This password is used if the user needs to be created during <em>Apply Access</em>.
                </div>
                <div class="managedb-flow-toolbar__prefix">
                    <input type="text" value="{{ $prefix }}_" readonly aria-label="User prefix">
                    <input id="managedb-modal-user-name" type="text" placeholder="username" required>
                </div>
                <input id="managedb-modal-user-password" type="text" placeholder="Password (min 8 chars)" minlength="8" required>
                <div class="managedb-password-strength-wrap">
                    <div class="managedb-password-strength-bar">
                        <span id="managedb-password-strength-fill" class="managedb-password-strength-fill is-weak" style="width: 20%;"></span>
                    </div>
                    <p id="managedb-password-strength" class="managedb-password-strength is-weak">
                        Strength: Weak - Use at least 8 chars with upper/lowercase, number, and symbol.
                    </p>
                </div>
                <div class="managedb-modal__actions">
                    <button type="button" class="btn-secondary" data-flow-action="close-user-modal">Cancel</button>
                    <button type="submit" class="btn-primary">Add User Node</button>
                </div>
            </form>
        </dialog>
        <dialog id="managedb-db-node-modal" class="managedb-modal">
            <form method="dialog" id="managedb-db-node-form" class="managedb-modal__form">
                <h3>Add Database Node</h3>
                <p class="managedb-modal__desc">Create a database node for this host. The host prefix is locked and always applied automatically.</p>
                <div class="managedb-modal__tip">
                    <strong>Tip:</strong> Use a clear database suffix (example: <code>app</code> or <code>adminer</code>). The full name becomes
                    <code>{{ $prefix }}_your_name</code>.
                </div>
                <div class="managedb-flow-toolbar__prefix">
                    <input type="text" value="{{ $prefix }}_" readonly aria-label="Database prefix">
                    <input id="managedb-modal-db-name" type="text" placeholder="database_name" required>
                </div>
                <div class="managedb-modal__actions">
                    <button type="button" class="btn-secondary" data-flow-action="close-db-modal">Cancel</button>
                    <button type="submit" class="btn-primary">Add Database Node</button>
                </div>
            </form>
        </dialog>
        <dialog id="managedb-edit-user-modal" class="managedb-modal">
            <form method="dialog" id="managedb-edit-user-form" class="managedb-modal__form">
                <h3>Edit User Node</h3>
                <p class="managedb-modal__desc">Update username and password for this user node.</p>
                <div class="managedb-flow-toolbar__prefix">
                    <input type="text" value="{{ $prefix }}_" readonly aria-label="User prefix">
                    <input id="managedb-edit-user-name" type="text" placeholder="username" required>
                </div>
                <input id="managedb-edit-user-password" type="text" placeholder="New password (optional, min 8 chars)">
                <div class="managedb-modal__actions">
                    <button type="button" class="btn-secondary" data-flow-action="close-edit-user-modal">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </dialog>
        <dialog id="managedb-user-details-modal" class="managedb-modal">
            <form method="dialog" class="managedb-modal__form">
                <h3>User Details</h3>
                <p class="managedb-modal__desc">Access and credential summary for selected user node.</p>
                <div class="managedb-modal__kv">
                    <span>User</span>
                    <strong id="managedb-user-details-name">-</strong>
                </div>
                <div class="managedb-modal__kv managedb-modal__kv--password">
                    <span>Password</span>
                    <div class="managedb-modal__password-wrap">
                        <input id="managedb-user-details-password" type="password" value="" readonly>
                        <button type="button" class="managedb-modal__eye-btn" data-flow-action="toggle-user-details-password" aria-label="Show password">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="managedb-modal__kv">
                    <span>Privileged databases</span>
                    <strong id="managedb-user-details-count">0</strong>
                </div>
                <div id="managedb-user-details-list" class="managedb-modal__db-list"></div>
                <div class="managedb-modal__actions">
                    <button type="button" class="btn-secondary" data-flow-action="close-user-details-modal">Close</button>
                </div>
            </form>
        </dialog>
        <dialog id="managedb-db-details-modal" class="managedb-modal managedb-modal--db-details">
            <form method="dialog" class="managedb-modal__form">
                <h3>Database info</h3>
                <p class="managedb-modal__desc">Connection details and users with access to this database (from the diagram and server grants).</p>
                <div class="managedb-modal__kv">
                    <span>Database name</span>
                    <strong id="managedb-db-details-name">-</strong>
                </div>
                <div class="managedb-modal__kv">
                    <span>Host</span>
                    <strong id="managedb-db-details-host">-</strong>
                </div>
                <div class="managedb-modal__kv">
                    <span>Port</span>
                    <strong id="managedb-db-details-port">-</strong>
                </div>
                <div class="managedb-modal__kv">
                    <span>MySQL user host</span>
                    <strong id="managedb-db-details-userhost">-</strong>
                </div>
                <p class="managedb-modal__tip managedb-modal__dsn" id="managedb-db-details-dsn-wrap">DSN <code id="managedb-db-details-dsn"></code></p>
                <p class="managedb-empty" id="managedb-db-details-users-empty" hidden>No users found for this database in the diagram or grants.</p>
                <div id="managedb-db-details-users" class="managedb-modal__db-access-list" aria-label="Users with access"></div>
                <div class="managedb-modal__actions">
                    <button type="button" class="btn-secondary" data-flow-action="close-db-details-modal">Close</button>
                </div>
            </form>
        </dialog>

        <script src="https://cdn.jsdelivr.net/npm/d3@7/dist/d3.min.js"></script>
        <script>
            (function () {
                const board = document.getElementById('managedb-flow-board');
                const svgEl = document.getElementById('managedb-flow-svg');
                if (!board || !svgEl || typeof window.d3 === 'undefined') {
                    return;
                }

                const d3 = window.d3;
                const users = JSON.parse(board.dataset.users || '[]');
                const userPasswords = JSON.parse(board.dataset.userPasswords || '{}');
                const databases = JSON.parse(board.dataset.databases || '[]');
                const grants = JSON.parse(board.dataset.grants || '[]');
                const mysqlInfo = JSON.parse(board.dataset.mysqlInfo || '{}');
                const adminerOpenUrl = String(board.dataset.adminerOpenUrl || '').trim();
                const escAttr = (s) => String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;');
                const escHtml = (s) => String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                const prefix = @json($prefix . '_');
                const flowStorageKey = @json('managedb.flow.' . $hosting->id);
                const flowGridKey = `${flowStorageKey}.grid_enabled`;
                const state = { nodes: [], edges: [], seq: 0 };
                let selectedNodeId = null;
                let connectingFrom = null;
                let tempLink = null;
                let contextNodeId = null;
                let contextNodeKind = null;
                let editingUserNodeId = null;
                const contextMenu = document.getElementById('managedb-flow-context-menu');

                const normalizeName = (value, fallback) => {
                    const v = (value || '').trim();
                    if (!v) return fallback;
                    return v;
                };

                const ensurePrefixed = (value) => {
                    const v = (value || '').trim();
                    if (!v) return prefix;
                    return v.startsWith(prefix) ? v : `${prefix}${v}`;
                };

                const addNode = (kind, label, x, y, extra = {}) => {
                    const id = `node-${++state.seq}`;
                    const node = { id, kind, label, x, y, ...extra };
                    state.nodes.push(node);
                    return node;
                };

                const seedDefaultNodes = () => {
                    state.nodes = [];
                    state.edges = [];
                    state.seq = 0;
                    const savedPositionsByKey = {};
                    try {
                        const rawSavedFlow = window.localStorage.getItem(flowStorageKey);
                        if (rawSavedFlow) {
                            const savedFlow = JSON.parse(rawSavedFlow);
                            if (savedFlow && Array.isArray(savedFlow.nodes)) {
                                savedFlow.nodes.forEach((node) => {
                                    const label = String(node.label ?? '').toLowerCase();
                                    const kind = node.kind === 'database' ? 'database' : 'user';
                                    if (!label) return;
                                    savedPositionsByKey[`${kind}:${label}`] = {
                                        x: Number(node.x) || 0,
                                        y: Number(node.y) || 0,
                                    };
                                });
                            }
                        }
                    } catch (error) {
                        // Fallback to default seed data when local storage is unavailable/corrupt.
                    }

                    const boardHeight = Math.max(460, board.clientHeight || 460);
                    const nodeIdByKey = {};

                    users.forEach((name, idx) => {
                        const label = String(name).toLowerCase();
                        const key = `user:${label}`;
                        const fallbackX = 44;
                        const fallbackY = 36 + (idx * 84) % (boardHeight - 120);
                        const pos = savedPositionsByKey[key] ?? { x: fallbackX, y: fallbackY };
                        const node = addNode('user', label, pos.x, pos.y, {
                            password: String(userPasswords[label] || ''),
                        });
                        nodeIdByKey[key] = node.id;
                    });
                    databases.forEach((name, idx) => {
                        const label = String(name).toLowerCase();
                        const key = `database:${label}`;
                        const fallbackX = 520;
                        const fallbackY = 36 + (idx * 84) % (boardHeight - 120);
                        const pos = savedPositionsByKey[key] ?? { x: fallbackX, y: fallbackY };
                        const node = addNode('database', label, pos.x, pos.y);
                        nodeIdByKey[key] = node.id;
                    });

                    grants.forEach((grant) => {
                        const userKey = `user:${String(grant.user ?? '').toLowerCase()}`;
                        const dbKey = `database:${String(grant.database ?? '').toLowerCase()}`;
                        const fromId = nodeIdByKey[userKey];
                        const toId = nodeIdByKey[dbKey];
                        if (!fromId || !toId) return;
                        state.edges.push({
                            key: makeEdgeKey(fromId, toId),
                            fromId,
                            toId,
                        });
                    });

                };

                const getNodeById = (id) => state.nodes.find((n) => n.id === id);

                const makeEdgeKey = (fromId, toId) => `${fromId}::${toId}`;

                const hasEdge = (fromId, toId) => state.edges.some((e) => e.key === makeEdgeKey(fromId, toId));
                const getSize = () => ({
                    width: Math.max(720, board.clientWidth || 720),
                    height: Math.max(460, board.clientHeight || 460),
                });

                const svg = d3.select(svgEl);
                const zoomLayer = svg.append('g').attr('class', 'managedb-d3-zoom-layer');
                const linkLayer = zoomLayer.append('g').attr('class', 'managedb-d3-links');
                const nodeLayer = zoomLayer.append('g').attr('class', 'managedb-d3-nodes');

                const zoomBehavior = d3.zoom()
                    .scaleExtent([0.7, 1.8])
                    .filter((event) => event.type !== 'wheel')
                    .on('zoom', (event) => {
                        zoomLayer.attr('transform', event.transform);
                    });

                svg.call(zoomBehavior);

                const removeNode = (id) => {
                    state.nodes = state.nodes.filter((n) => n.id !== id);
                    state.edges = state.edges.filter((e) => e.fromId !== id && e.toId !== id);
                    if (selectedNodeId === id) {
                        selectedNodeId = null;
                    }
                    render();
                };

                const removeNodeLinks = (id) => {
                    state.edges = state.edges.filter((e) => e.fromId !== id && e.toId !== id);
                    render();
                };

                const connectNodes = (fromId, toId) => {
                    const fromNode = getNodeById(fromId);
                    const toNode = getNodeById(toId);
                    if (!fromNode || !toNode || fromNode.kind !== 'user' || toNode.kind !== 'database') return;
                    const key = makeEdgeKey(fromId, toId);
                    if (hasEdge(fromId, toId)) {
                        state.edges = state.edges.filter((e) => e.key !== key);
                    } else {
                        state.edges.push({ key, fromId, toId });
                    }
                    render();
                };

                const clampPosition = (x, y) => {
                    const size = getSize();
                    const maxX = Math.max(0, size.width - 200);
                    const maxY = Math.max(0, size.height - 100);
                    return { x: Math.max(8, Math.min(maxX, x)), y: Math.max(8, Math.min(maxY, y)) };
                };

                const edgePath = (edge) => {
                    const fromNode = getNodeById(edge.fromId);
                    const toNode = getNodeById(edge.toId);
                    if (!fromNode || !toNode) return '';
                    const x1 = fromNode.x + 188;
                    const y1 = fromNode.y + 45;
                    const x2 = toNode.x + 12;
                    const y2 = toNode.y + 45;
                    const c1 = x1 + Math.max(40, (x2 - x1) / 2);
                    const c2 = x2 - Math.max(40, (x2 - x1) / 2);
                    return `M ${x1} ${y1} C ${c1} ${y1}, ${c2} ${y2}, ${x2} ${y2}`;
                };

                const labelForDisplay = (label) => {
                    const text = String(label || '');
                    const max = 24;
                    if (text.length <= max) return text;
                    return `${text.slice(0, max - 1)}...`;
                };

                const labelFontSize = (label) => {
                    const len = String(label || '').length;
                    if (len > 34) return 10;
                    if (len > 26) return 11;
                    return 12.4;
                };

                const hideContextMenu = () => {
                    if (!contextMenu) return;
                    contextMenu.hidden = true;
                    contextNodeId = null;
                    contextNodeKind = null;
                };

                const graphPayload = () => ({
                    nodes: state.nodes.map((node) => ({
                        id: node.id,
                        kind: node.kind,
                        label: node.label,
                        x: node.x,
                        y: node.y,
                        password: node.kind === 'user' ? String(node.password ?? '') : '',
                    })),
                    edges: state.edges.map((edge) => ({
                        fromId: edge.fromId,
                        toId: edge.toId,
                    })),
                });

                const getUserPrivilegedDatabases = (userNodeId) => {
                    const dbIds = state.edges
                        .filter((edge) => edge.fromId === userNodeId)
                        .map((edge) => edge.toId);
                    return Array.from(new Set(dbIds))
                        .map((id) => getNodeById(id))
                        .filter((n) => n && n.kind === 'database')
                        .map((n) => n.label)
                        .sort();
                };

                const getPasswordForUserNode = (userNode) => {
                    if (!userNode) return '';
                    const fromNode = String(userNode.password || '').trim();
                    const fromStore = String(userPasswords[String(userNode.label || '').toLowerCase()] || '').trim();
                    return fromNode || fromStore;
                };

                const getAccessRowsForDatabase = (dbNode) => {
                    const dbLabel = String(dbNode.label || '').toLowerCase();
                    const map = new Map();
                    state.edges
                        .filter((e) => e.toId === dbNode.id)
                        .forEach((e) => {
                            const u = getNodeById(e.fromId);
                            if (u && u.kind === 'user' && !map.has(u.label)) {
                                map.set(u.label, { fromDiagram: true });
                            }
                        });
                    grants.forEach((g) => {
                        if (String(g.database || '').toLowerCase() !== dbLabel) return;
                        const uname = String(g.user || '').toLowerCase();
                        if (!uname || map.has(uname)) return;
                        map.set(uname, { fromDiagram: false });
                    });
                    return Array.from(map.entries())
                        .map(([username, meta]) => {
                            const node = state.nodes.find((n) => n.kind === 'user' && n.label === username);
                            const pwd = node ? getPasswordForUserNode(node) : String(userPasswords[username] || '').trim();
                            return { username, password: pwd, fromDiagram: meta.fromDiagram };
                        })
                        .sort((a, b) => a.username.localeCompare(b.username));
                };

                const saveGraph = () => {
                    const payload = graphPayload();
                    try {
                        window.localStorage.setItem(flowStorageKey, JSON.stringify(payload));
                        return true;
                    } catch (error) {
                        return false;
                    }
                };

                const openUserDetailsModal = (userNodeId) => {
                    const node = getNodeById(userNodeId);
                    if (!node || node.kind !== 'user' || !userDetailsModal) return;
                    const databases = getUserPrivilegedDatabases(userNodeId);
                    if (userDetailsNameEl) {
                        userDetailsNameEl.textContent = node.label;
                    }
                    const effectivePwd = getPasswordForUserNode(node);
                    const hasPassword = effectivePwd !== '';
                    if (userDetailsPasswordEl) {
                        userDetailsPasswordEl.value = hasPassword ? effectivePwd : 'Not stored';
                        userDetailsPasswordEl.type = 'password';
                    }
                    const icon = toggleUserDetailsPasswordBtn?.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                    if (userDetailsCountEl) {
                        userDetailsCountEl.textContent = String(databases.length);
                    }
                    if (userDetailsListEl) {
                        if (databases.length === 0) {
                            userDetailsListEl.innerHTML = '<p class="managedb-empty">No database privileges linked in diagram.</p>';
                        } else {
                            userDetailsListEl.innerHTML = databases.map((dbName) => `<code>${dbName}</code>`).join('');
                        }
                    }
                    userDetailsModal.showModal();
                };

                const openDatabaseDetailsModal = (dbNodeId) => {
                    const node = getNodeById(dbNodeId);
                    const modal = document.getElementById('managedb-db-details-modal');
                    if (!node || node.kind !== 'database' || !modal) return;
                    const nameEl = document.getElementById('managedb-db-details-name');
                    const hostEl = document.getElementById('managedb-db-details-host');
                    const portEl = document.getElementById('managedb-db-details-port');
                    const userhostEl = document.getElementById('managedb-db-details-userhost');
                    const dsnCodeEl = document.getElementById('managedb-db-details-dsn');
                    const usersEl = document.getElementById('managedb-db-details-users');
                    const usersEmptyEl = document.getElementById('managedb-db-details-users-empty');
                    const host = String(mysqlInfo.host || '—');
                    const port = Number(mysqlInfo.port) || 3306;
                    const userHost = String(mysqlInfo.user_host != null ? mysqlInfo.user_host : '%');
                    if (nameEl) nameEl.textContent = node.label;
                    if (hostEl) hostEl.textContent = host;
                    if (portEl) portEl.textContent = String(port);
                    if (userhostEl) userhostEl.textContent = userHost;
                    if (dsnCodeEl) {
                        dsnCodeEl.textContent = `mysql:host=${host};port=${port};dbname=${node.label};charset=utf8mb4`;
                    }
                    const rows = getAccessRowsForDatabase(node);
                    if (usersEmptyEl) {
                        usersEmptyEl.hidden = rows.length > 0;
                    }
                    if (usersEl) {
                        if (rows.length === 0) {
                            usersEl.innerHTML = '';
                        } else {
                            usersEl.innerHTML = rows
                                .map((row) => {
                                    const display = row.password ? row.password : 'Not stored';
                                    const source = row.fromDiagram ? 'Diagram' : 'Grants only';
                                    return [
                                        '<div class="managedb-modal__db-access-block">',
                                        '<div class="managedb-modal__kv"><span>User <span class="managedb-modal__meta">(',
                                        source,
                                        ')</span></span><strong><code>',
                                        escHtml(row.username),
                                        '</code></strong></div>',
                                        '<div class="managedb-modal__kv managedb-modal__kv--password">',
                                        '<span>Password</span>',
                                        '<div class="managedb-modal__password-wrap">',
                                        '<input type="password" class="managedb-db-access-pwd" readonly value="',
                                        escAttr(display),
                                        '">',
                                        '<button type="button" class="managedb-modal__eye-btn" data-flow-action="toggle-db-access-password" aria-label="Show password">',
                                        '<i class="fa fa-eye" aria-hidden="true"></i>',
                                        '</button></div></div></div>',
                                    ].join('');
                                })
                                .join('');
                        }
                    }
                    modal.showModal();
                };

                const showContextMenu = (nodeId, nodeKind, clientX, clientY) => {
                    if (!contextMenu) return;
                    contextNodeId = nodeId;
                    contextNodeKind = nodeKind;
                    contextMenu.hidden = false;
                    const userDetailsMenuBtn = document.getElementById('managedb-menu-user-details');
                    if (userDetailsMenuBtn) {
                        userDetailsMenuBtn.hidden = nodeKind !== 'user';
                    }
                    const openAdminerMenuBtn = document.getElementById('managedb-menu-open-adminer');
                    if (openAdminerMenuBtn) {
                        openAdminerMenuBtn.hidden = nodeKind !== 'user' || !adminerOpenUrl;
                    }
                    const dbDetailsMenuBtn = document.getElementById('managedb-menu-db-details');
                    if (dbDetailsMenuBtn) {
                        dbDetailsMenuBtn.hidden = nodeKind !== 'database';
                    }
                    const rect = board.getBoundingClientRect();
                    const menuWidth = contextMenu.offsetWidth || 160;
                    const menuHeight = contextMenu.offsetHeight || 80;
                    const left = Math.max(8, Math.min(rect.width - menuWidth - 8, clientX - rect.left));
                    const top = Math.max(8, Math.min(rect.height - menuHeight - 8, clientY - rect.top));
                    contextMenu.style.left = `${left}px`;
                    contextMenu.style.top = `${top}px`;
                };

                const dragNode = d3.drag()
                    .on('start', function () {
                        d3.select(this).classed('is-dragging', true);
                    })
                    .on('drag', function (event, d) {
                        const p = clampPosition(event.x, event.y);
                        d.x = p.x;
                        d.y = p.y;
                        d3.select(this).attr('transform', `translate(${d.x},${d.y})`);
                        linkLayer.selectAll('.managedb-d3-edge').attr('d', edgePath);
                        if (tempLink) {
                            tempLink.attr('d', edgePath(tempLink.datum()));
                        }
                    })
                    .on('end', function () {
                        d3.select(this).classed('is-dragging', false);
                    });

                const connectDrag = d3.drag()
                    .on('start', function (event, d) {
                        event.sourceEvent.stopPropagation();
                        connectingFrom = d.id;
                        tempLink = linkLayer.append('path')
                            .datum({ fromId: d.id, toId: d.id })
                            .attr('class', 'managedb-flow-edge managedb-flow-edge--temp')
                            .attr('d', edgePath({ fromId: d.id, toId: d.id }));
                    })
                    .on('drag', function (event, d) {
                        if (!tempLink) return;
                        const startX = d.x + 188;
                        const startY = d.y + 45;
                        const pointer = d3.pointer(event, zoomLayer.node());
                        const endX = pointer[0];
                        const endY = pointer[1];
                        const c1 = startX + Math.max(40, (endX - startX) / 2);
                        const c2 = endX - Math.max(40, (endX - startX) / 2);
                        tempLink.attr('d', `M ${startX} ${startY} C ${c1} ${startY}, ${c2} ${endY}, ${endX} ${endY}`);
                    })
                    .on('end', function (event) {
                        if (tempLink) {
                            tempLink.remove();
                            tempLink = null;
                        }
                        const sourceEvent = event.sourceEvent;
                        const targetEl = sourceEvent ? document.elementFromPoint(sourceEvent.clientX, sourceEvent.clientY) : null;
                        const targetNodeEl = targetEl ? targetEl.closest('.managedb-d3-node') : null;
                        const toId = targetNodeEl ? targetNodeEl.getAttribute('data-node-id') : null;
                        if (connectingFrom && toId) {
                            connectNodes(connectingFrom, toId);
                        }
                        connectingFrom = null;
                    });

                const render = () => {
                    const size = getSize();
                    svg.attr('viewBox', `0 0 ${size.width} ${size.height}`);

                    linkLayer
                        .selectAll('.managedb-d3-edge')
                        .data(state.edges, (d) => d.key)
                        .join(
                            (enter) => enter.append('path').attr('class', 'managedb-flow-edge managedb-d3-edge'),
                            (update) => update,
                            (exit) => exit.remove()
                        )
                        .attr('d', edgePath);

                    const nodeJoin = nodeLayer
                        .selectAll('.managedb-d3-node')
                        .data(state.nodes, (d) => d.id)
                        .join(
                            (enter) => {
                                const g = enter.append('g')
                                    .attr('class', (d) => `managedb-d3-node managedb-d3-node--${d.kind}`)
                                    .attr('data-node-id', (d) => d.id);

                                g.append('rect')
                                    .attr('class', 'managedb-d3-node__body')
                                    .attr('width', 190)
                                    .attr('height', 90)
                                    .attr('rx', 12)
                                    .attr('ry', 12);

                                g.append('text')
                                    .attr('class', 'managedb-d3-node__kind')
                                    .attr('x', 36)
                                    .attr('y', 20)
                                    .text((d) => d.kind === 'user' ? 'USER' : 'DATABASE');

                                g.append('text')
                                    .attr('class', 'managedb-d3-node__label')
                                    .attr('x', 12)
                                    .attr('y', 48)
                                    .style('font-size', (d) => `${labelFontSize(d.label)}px`)
                                    .text((d) => labelForDisplay(d.label));

                                g.append('title')
                                    .attr('class', 'managedb-d3-node__title')
                                    .text((d) => d.label);

                                g.append('circle')
                                    .attr('class', 'managedb-d3-node__badge')
                                    .attr('cx', 18)
                                    .attr('cy', 16)
                                    .attr('r', 9);

                                g.append('text')
                                    .attr('class', 'managedb-d3-node__badge-text')
                                    .attr('x', 18)
                                    .attr('y', 20)
                                    .text((d) => d.kind === 'user' ? 'U' : 'D');

                                g.append('text')
                                    .attr('class', 'managedb-d3-node__remove')
                                    .attr('x', 174)
                                    .attr('y', 20)
                                    .text('x')
                                    .on('click', (event, d) => {
                                        event.stopPropagation();
                                        removeNode(d.id);
                                    });

                                g.filter((d) => d.kind === 'user')
                                    .append('circle')
                                    .attr('class', 'managedb-d3-node__connector')
                                    .attr('cx', 188)
                                    .attr('cy', 45)
                                    .attr('r', 7)
                                    .call(connectDrag);

                                return g.call(dragNode);
                            },
                            (update) => update,
                            (exit) => exit.remove()
                        )
                        .attr('transform', (d) => `translate(${d.x},${d.y})`)
                        .classed('is-selected', (d) => d.id === selectedNodeId)
                        .on('click', (event, d) => {
                            event.stopPropagation();
                            hideContextMenu();
                            selectedNodeId = d.id;
                            render();
                        })
                        .on('contextmenu', (event, d) => {
                            event.preventDefault();
                            event.stopPropagation();
                            selectedNodeId = d.id;
                            render();
                            showContextMenu(d.id, d.kind, event.clientX, event.clientY);
                        })
                        .on('dblclick', (event, d) => {
                            if (d.kind !== 'user') return;
                            event.preventDefault();
                            event.stopPropagation();
                            editingUserNodeId = d.id;
                            const current = String(d.label || '');
                            editUserName.value = current.startsWith(prefix) ? current.slice(prefix.length) : current;
                            editUserPassword.value = '';
                            editUserModal?.showModal();
                        });

                    nodeJoin
                        .select('.managedb-d3-node__label')
                        .style('font-size', (d) => `${labelFontSize(d.label)}px`)
                        .text((d) => labelForDisplay(d.label));
                    nodeJoin.select('.managedb-d3-node__title').text((d) => d.label);
                };

                svg.on('click', () => {
                    selectedNodeId = null;
                    hideContextMenu();
                    render();
                });

                if (contextMenu) {
                    contextMenu.addEventListener('click', (event) => {
                        const trigger = event.target && event.target.closest
                            ? event.target.closest('[data-menu-action]')
                            : null;
                        const action = (trigger && trigger.getAttribute('data-menu-action'))
                            || (event.target && event.target.getAttribute
                                ? event.target.getAttribute('data-menu-action') : null);
                        if (!action || !contextNodeId) return;
                        if (action === 'delete-node') {
                            removeNode(contextNodeId);
                        }
                        if (action === 'remove-links') {
                            removeNodeLinks(contextNodeId);
                        }
                        if (action === 'view-user-details' && contextNodeKind === 'user') {
                            openUserDetailsModal(contextNodeId);
                        }
                        if (action === 'open-in-adminer' && contextNodeKind === 'user' && adminerOpenUrl) {
                            const node = getNodeById(contextNodeId);
                            if (node) {
                                const dbs = getUserPrivilegedDatabases(contextNodeId);
                                try {
                                    const u = new URL(adminerOpenUrl, window.location.origin);
                                    u.searchParams.set('mysql_user', String(node.label || ''));
                                    if (dbs[0]) u.searchParams.set('db', dbs[0]);
                                    window.open(u.toString(), '_blank', 'noopener,noreferrer');
                                } catch (err) {
                                    window.open(adminerOpenUrl, '_blank', 'noopener,noreferrer');
                                }
                            }
                        }
                        if (action === 'view-db-details' && contextNodeKind === 'database') {
                            openDatabaseDetailsModal(contextNodeId);
                        }
                        hideContextMenu();
                    });
                }

                document.addEventListener('click', (event) => {
                    if (!contextMenu || contextMenu.hidden) return;
                    if (contextMenu.contains(event.target)) return;
                    hideContextMenu();
                });

                const userModal = document.getElementById('managedb-user-node-modal');
                const dbModal = document.getElementById('managedb-db-node-modal');
                const editUserModal = document.getElementById('managedb-edit-user-modal');
                const userModalForm = document.getElementById('managedb-user-node-form');
                const dbModalForm = document.getElementById('managedb-db-node-form');
                const editUserModalForm = document.getElementById('managedb-edit-user-form');
                const modalUserName = document.getElementById('managedb-modal-user-name');
                const modalUserPassword = document.getElementById('managedb-modal-user-password');
                const modalDbName = document.getElementById('managedb-modal-db-name');
                const editUserName = document.getElementById('managedb-edit-user-name');
                const editUserPassword = document.getElementById('managedb-edit-user-password');
                const userDetailsModal = document.getElementById('managedb-user-details-modal');
                const userDetailsNameEl = document.getElementById('managedb-user-details-name');
                const userDetailsPasswordEl = document.getElementById('managedb-user-details-password');
                const userDetailsCountEl = document.getElementById('managedb-user-details-count');
                const userDetailsListEl = document.getElementById('managedb-user-details-list');
                const passwordStrengthEl = document.getElementById('managedb-password-strength');
                const passwordStrengthFillEl = document.getElementById('managedb-password-strength-fill');
                const closeUserModalBtn = document.querySelector('[data-flow-action="close-user-modal"]');
                const closeDbModalBtn = document.querySelector('[data-flow-action="close-db-modal"]');
                const closeEditUserModalBtn = document.querySelector('[data-flow-action="close-edit-user-modal"]');
                const closeUserDetailsModalBtn = document.querySelector('[data-flow-action="close-user-details-modal"]');
                const closeDbDetailsModalBtn = document.querySelector('[data-flow-action="close-db-details-modal"]');
                const dbDetailsModalEl = document.getElementById('managedb-db-details-modal');
                const toggleUserDetailsPasswordBtn = document.querySelector('[data-flow-action="toggle-user-details-password"]');
                const gridToggleBtn = document.querySelector('[data-flow-action="toggle-grid"]');
                const applyAccessForm = document.getElementById('managedb-apply-access-form');
                const graphPayloadInput = document.getElementById('managedb-graph-payload');

                const applyGridState = (enabled) => {
                    board.classList.toggle('managedb-flow-board--grid-hidden', !enabled);
                    if (gridToggleBtn) {
                        gridToggleBtn.classList.toggle('is-active', enabled);
                        gridToggleBtn.setAttribute('data-grid-enabled', enabled ? '1' : '0');
                    }
                };

                const loadGridState = () => {
                    try {
                        const stored = window.localStorage.getItem(flowGridKey);
                        if (stored === null) return true;
                        return stored === '1';
                    } catch (error) {
                        return true;
                    }
                };

                const saveGridState = (enabled) => {
                    try {
                        window.localStorage.setItem(flowGridKey, enabled ? '1' : '0');
                    } catch (error) {
                        // Ignore local storage failures.
                    }
                };

                const evaluatePasswordStrength = (value) => {
                    const password = String(value || '');
                    let score = 0;
                    if (password.length >= 8) score += 1;
                    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 1;
                    if (/\d/.test(password)) score += 1;
                    if (/[^A-Za-z0-9]/.test(password)) score += 1;
                    if (password.length >= 12) score += 1;

                    if (score >= 4) {
                        return {
                            level: 'strong',
                            progress: 100,
                            text: 'Strength: Strong - Good password quality.',
                        };
                    }
                    if (score >= 2) {
                        return {
                            level: 'medium',
                            progress: 62,
                            text: 'Strength: Medium - Add more complexity for stronger security.',
                        };
                    }
                    return {
                        level: 'weak',
                        progress: 20,
                        text: 'Strength: Weak - Use at least 8 chars with upper/lowercase, number, and symbol.',
                    };
                };

                const updatePasswordStrength = () => {
                    if (!passwordStrengthEl) return;
                    const result = evaluatePasswordStrength(modalUserPassword?.value || '');
                    passwordStrengthEl.classList.remove('is-weak', 'is-medium', 'is-strong');
                    passwordStrengthEl.classList.add(`is-${result.level}`);
                    passwordStrengthEl.textContent = result.text;
                    if (passwordStrengthFillEl) {
                        passwordStrengthFillEl.classList.remove('is-weak', 'is-medium', 'is-strong');
                        passwordStrengthFillEl.classList.add(`is-${result.level}`);
                        passwordStrengthFillEl.style.width = `${result.progress}%`;
                    }
                };

                if (userModalForm) {
                    userModalForm.addEventListener('submit', (event) => {
                        event.preventDefault();
                        const raw = normalizeName(modalUserName?.value, '');
                        if (!raw) {
                            window.alert('Username is required.');
                            return;
                        }
                        const label = ensurePrefixed(raw);
                        const password = String(modalUserPassword?.value || '').trim();
                        if (password.length < 8) {
                            window.alert('Password must be at least 8 characters.');
                            return;
                        }
                        addNode('user', label, 44, 40 + (state.nodes.length * 24) % 260, { password });
                        if (modalUserName) modalUserName.value = '';
                        if (modalUserPassword) modalUserPassword.value = '';
                        updatePasswordStrength();
                        userModal.close();
                        render();
                    });
                }

                modalUserPassword?.addEventListener('input', updatePasswordStrength);

                if (dbModalForm) {
                    dbModalForm.addEventListener('submit', (event) => {
                        event.preventDefault();
                        const raw = normalizeName(modalDbName?.value, '');
                        if (!raw) {
                            window.alert('Database name is required.');
                            return;
                        }
                        const label = ensurePrefixed(raw);
                        addNode('database', label, 520, 40 + (state.nodes.length * 24) % 260);
                        if (modalDbName) modalDbName.value = '';
                        dbModal.close();
                        render();
                    });
                }

                if (editUserModalForm) {
                    editUserModalForm.addEventListener('submit', (event) => {
                        event.preventDefault();
                        if (!editingUserNodeId) return;
                        const node = getNodeById(editingUserNodeId);
                        if (!node || node.kind !== 'user') return;

                        const raw = normalizeName(editUserName?.value, '');
                        if (!raw) {
                            window.alert('User name is required.');
                            return;
                        }
                        node.label = ensurePrefixed(raw);

                        const newPassword = String(editUserPassword?.value || '').trim();
                        if (newPassword !== '') {
                            if (newPassword.length < 8) {
                                window.alert('Password must be at least 8 characters.');
                                return;
                            }
                            node.password = newPassword;
                        }

                        editUserModal.close();
                        editingUserNodeId = null;
                        saveGraph();
                        render();
                    });
                }

                closeUserModalBtn?.addEventListener('click', () => {
                    userModal?.close();
                });

                closeDbModalBtn?.addEventListener('click', () => {
                    dbModal?.close();
                });

                closeEditUserModalBtn?.addEventListener('click', () => {
                    editUserModal?.close();
                    editingUserNodeId = null;
                });

                closeUserDetailsModalBtn?.addEventListener('click', () => {
                    userDetailsModal?.close();
                });

                closeDbDetailsModalBtn?.addEventListener('click', () => {
                    dbDetailsModalEl?.close();
                });

                dbDetailsModalEl?.addEventListener('click', (event) => {
                    const btn = event.target.closest('[data-flow-action="toggle-db-access-password"]');
                    if (!btn) return;
                    const wrap = btn.closest('.managedb-modal__password-wrap');
                    const input = wrap && wrap.querySelector('.managedb-db-access-pwd');
                    if (!input) return;
                    const nextType = input.type === 'password' ? 'text' : 'password';
                    input.type = nextType;
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye', nextType === 'password');
                        icon.classList.toggle('fa-eye-slash', nextType === 'text');
                    }
                });

                toggleUserDetailsPasswordBtn?.addEventListener('click', () => {
                    if (!userDetailsPasswordEl) return;
                    const nextType = userDetailsPasswordEl.type === 'password' ? 'text' : 'password';
                    userDetailsPasswordEl.type = nextType;
                    const icon = toggleUserDetailsPasswordBtn.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye', nextType === 'password');
                        icon.classList.toggle('fa-eye-slash', nextType === 'text');
                    }
                });

                board.closest('.managedb-card--flow')?.addEventListener('click', (event) => {
                    const action = event.target.getAttribute('data-flow-action');
                    if (action === 'open-add-user-modal') {
                        hideContextMenu();
                        updatePasswordStrength();
                        userModal?.showModal();
                    }
                    if (action === 'toggle-grid') {
                        const currentlyEnabled = gridToggleBtn?.getAttribute('data-grid-enabled') !== '0';
                        const nextEnabled = !currentlyEnabled;
                        applyGridState(nextEnabled);
                        saveGridState(nextEnabled);
                    }
                    if (action === 'open-add-db-modal') {
                        hideContextMenu();
                        dbModal?.showModal();
                    }
                    if (action === 'apply-access') {
                        hideContextMenu();
                        saveGraph();
                        if (!applyAccessForm || !graphPayloadInput) {
                            window.alert('Apply access form is missing.');
                            return;
                        }
                        graphPayloadInput.value = JSON.stringify(graphPayload());
                        applyAccessForm.submit();
                    }
                });

                window.addEventListener('resize', render);
                applyGridState(loadGridState());
                seedDefaultNodes();
                render();
            })();
        </script>
    @endif

    @if ($activeTab === 'users')
        <div class="managedb-grid managedb-grid--users">
            <section class="server-card managedb-card">
                <div class="managedb-card__head">
                    <h2>Create MySQL User</h2>
                    <p>Create user and optionally grant privileges to one database.</p>
                </div>
                <form class="managedb-form" method="POST" action="{{ route('hosts.db.create-user', $hosting) }}">
                    @csrf
                    <label for="user-name">User name (without prefix)</label>
                    <div class="managedb-input-prefix">
                        <span>{{ $prefix }}_</span>
                        <input id="user-name" type="text" name="name" placeholder="appuser" required>
                    </div>

                    <label for="user-password">Password</label>
                    <input id="user-password" type="text" name="password" placeholder="Strong password" required>

                    <label for="user-database">Grant to database (optional)</label>
                    <select id="user-database" name="database">
                        <option value="">-- no grant now --</option>
                        @foreach ($databases as $database)
                            <option value="{{ $database }}">{{ $database }}</option>
                        @endforeach
                    </select>
                    <div class="managedb-actions">
                        <button class="btn-primary" type="submit">Create User</button>
                    </div>
                </form>
            </section>

            <section class="server-card managedb-card">
                <div class="managedb-card__head">
                    <h3>MySQL Users (Prefixed)</h3>
                </div>
                @if (!empty($usersListRestricted))
                    <p class="managedb-empty">User list is hidden because current MySQL account has limited privileges.</p>
                @elseif ($loadUsersError)
                    <p class="managedb-empty">{{ $loadUsersError }}</p>
                @elseif (count($users) === 0)
                    <p class="managedb-empty">No users found.</p>
                @else
                    <ul class="managedb-list">
                        @foreach ($users as $user)
                            <li><code>{{ $user }}</code></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif

    @if ($activeTab === 'databases')
        <div class="managedb-grid managedb-grid--databases">
            <section class="server-card managedb-card" id="mysql-create-db-wizard">
                <div class="managedb-card__head">
                    <h2>Create Database</h2>
                    <p>New database name will include host prefix automatically.</p>
                </div>
                <form class="managedb-form" method="POST" action="{{ route('hosts.db.create-database', $hosting) }}">
                    @csrf
                    <label for="db-name">Database name (without prefix)</label>
                    <div class="managedb-input-prefix">
                        <span>{{ $prefix }}_</span>
                        <input id="db-name" type="text" name="name" placeholder="app" required>
                    </div>
                    <div class="managedb-actions">
                        <button class="btn-primary" type="submit">Create Database</button>
                    </div>
                </form>
            </section>

            <section class="server-card managedb-card">
                <div class="managedb-card__head">
                    <h3>MySQL Databases (Prefixed)</h3>
                </div>
                @if (count($databases) === 0)
                    <p class="managedb-empty">No databases found.</p>
                @else
                    <ul class="managedb-list">
                        @foreach ($databases as $database)
                            <li><code>{{ $database }}</code></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif
</div>
@endsection
