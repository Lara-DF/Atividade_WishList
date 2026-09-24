<?php
// --- CONEXÃO COM O BANCO DE DADOS ---
$host = 'localhost';
$user = 'root';
$pass = '*Junior1974';
$dbname = 'wishlist_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// --- PROCESSAMENTO DE AÇÕES (CRUD) ---
$action = $_GET['action'] ?? '';

// 1. Cadastrar ou Atualizar produto
if (($action === 'cadastrar' || $action === 'editar') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $link = trim($_POST['link']);
    $preco = filter_var($_POST['preco'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $loja = trim($_POST['loja']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if (!empty($nome) && !empty($link) && !empty($preco) && !empty($loja)) {
        if ($action === 'editar' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, link = ?, preco = ?, loja = ? WHERE id = ?");
            $stmt->execute([$nome, $link, $preco, $loja, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO produtos (nome, link, preco, loja) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $link, $preco, $loja]);
        }
    }
    header('Location: index.php');
    exit;
}

// 2. Alternar Status (Comprado / Pendente)
if ($action === 'toggle_status') {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE produtos SET comprado = NOT comprado WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php');
    exit;
}

// 3. Eliminar Produto
if ($action === 'deletar') {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php');
    exit;
}

// --- VERIFICAR SE HÁ ITEM A EDITAR ---
$produtoEditar = null;
if ($action === 'carregar_edicao') {
    $idEditar = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$idEditar]);
    $produtoEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- CONSULTA PARA LISTAR PRODUTOS ---
$stmt = $pdo->query("SELECT * FROM produtos ORDER BY id DESC");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Métricas do Painel
$total_itens = count($produtos);
$total_comprados = count(array_filter($produtos, fn($p) => $p['comprado'] == 1));
$valor_total = array_reduce($produtos, fn($acc, $p) => $acc + $p['preco'], 0);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WishList • Minha Lista de Desejos</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --orange-main: #f97316;
            --orange-hover: #ea580c;
            --orange-dark: #c2410c;
            --orange-light: #ffedd5;
            --bg-body: #fff7ed;
            --card-bg: #ffffff;
            --text-main: #431407;
            --text-muted: #9a3412;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            padding-bottom: 3rem;
            font-size: 1.125rem; /* Aumentado a base do texto */
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--orange-main) !important;
            letter-spacing: -0.5px;
            font-size: 2rem; /* Marca maior e sem o fogo */
        }

        .hero-banner {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            border-radius: 24px;
            color: #ffffff;
            padding: 2.5rem;
            box-shadow: 0 15px 30px -5px rgba(249, 115, 22, 0.3);
        }

        .hero-banner h2 {
            font-size: 2.25rem;
        }

        .hero-banner p {
            font-size: 1.2rem;
        }

        .metric-card {
            background: #ffffff;
            border: 1px solid #fed7aa;
            border-radius: 18px;
            padding: 1.5rem;
            transition: transform 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-2px);
        }

        .metric-card .metric-label {
            font-size: 1.05rem;
        }

        .metric-card .metric-value {
            font-size: 1.75rem;
        }

        .app-card {
            background: #ffffff;
            border: 1px solid #fed7aa;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(249, 115, 22, 0.05);
        }

        .form-label {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .form-control {
            border-radius: 12px;
            border: 1px solid #fed7aa;
            padding: 0.85rem 1.1rem;
            font-size: 1.1rem;
            background-color: #fffaf5;
        }

        .form-control:focus {
            border-color: var(--orange-main);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.15);
        }

        .btn-orange {
            background-color: var(--orange-main);
            color: white;
            border-radius: 12px;
            padding: 0.85rem 1.6rem;
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.25);
        }

        .btn-orange:hover {
            background-color: var(--orange-hover);
            color: white;
            transform: translateY(-1px);
        }

        .table th {
            font-size: 0.95rem;
        }

        .table td {
            font-size: 1.1rem;
        }

        .badge-status-bought {
            background-color: #dcfce7;
            color: #15803d;
            font-weight: 700;
            padding: 0.5em 0.9em;
            border-radius: 10px;
            font-size: 1rem;
        }

        .badge-status-pending {
            background-color: var(--orange-light);
            color: var(--orange-dark);
            font-weight: 700;
            padding: 0.5em 0.9em;
            border-radius: 10px;
            font-size: 1rem;
        }

        .action-icon {
            color: var(--text-muted);
            padding: 0.5rem 0.75rem;
            font-size: 1.25rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .action-icon:hover {
            background-color: var(--orange-light);
            color: var(--orange-dark);
        }

        .action-icon.delete:hover {
            background-color: #fee2e2;
            color: #dc2626;
        }
    </style>
</head>
<body>

<!-- NAVEGAÇÃO SÓ COM TEXTO -->
<nav class="navbar navbar-expand-lg bg-white border-bottom border-warning-subtle py-3 mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            WishList
        </a>
        <span class="text-muted fw-semibold fs-5">Meu Painel de Desejos</span>
    </div>
</nav>

<div class="container">
    <!-- HERO BANNER -->
    <div class="hero-banner mb-4">
        <h2 class="fw-bold mb-2"><i class="bi bi-stars me-2"></i>Meus Desejos & Objetivos</h2>
        <p class="opacity-90 mb-0">Acompanhe e gerencie tudo o que você quer adquirir em um só lugar.</p>
    </div>

    <!-- CARDS DE MÉTRICAS -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="metric-card d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted metric-label d-block fw-semibold">Na Lista</span>
                    <span class="metric-value fw-extrabold text-warning-emphasis"><?= $total_itens ?> itens</span>
                </div>
                <div class="bg-warning-subtle p-3 rounded-circle text-warning-emphasis fs-3">
                    <i class="bi bi-gift-fill"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted metric-label d-block fw-semibold">Conquistados</span>
                    <span class="metric-value fw-extrabold text-success"><?= $total_comprados ?> de <?= $total_itens ?></span>
                </div>
                <div class="bg-success-subtle p-3 rounded-circle text-success fs-3">
                    <i class="bi bi-bag-check-fill"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted metric-label d-block fw-semibold">Estimativa Total</span>
                    <span class="metric-value fw-extrabold text-warning-emphasis">R$ <?= number_format($valor_total, 2, ',', '.') ?></span>
                </div>
                <div class="bg-warning-subtle p-3 rounded-circle text-warning-emphasis fs-3">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIO DE CADASTRO / EDIÇÃO -->
    <div class="app-card p-4 mb-4" id="formulario">
        <h4 class="fw-bold mb-3 text-warning-emphasis">
            <?php if ($produtoEditar): ?>
                <i class="bi bi-pencil-square me-2"></i> Editar Desejo
            <?php else: ?>
                <i class="bi bi-plus-circle-fill me-2"></i> Adicionar Novo Desejo
            <?php endif; ?>
        </h4>
        
        <form action="index.php?action=<?= $produtoEditar ? 'editar' : 'cadastrar' ?>" method="POST" id="formWishlist" onsubmit="return validarFormulario()">
            <?php if ($produtoEditar): ?>
                <input type="hidden" name="id" value="<?= $produtoEditar['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label text-secondary">Nome do Produto</label>
                    <input type="text" name="nome" id="nome" class="form-control" placeholder="Ex: Monitor Gamer" value="<?= htmlspecialchars($produtoEditar['nome'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-secondary">Link do Produto</label>
                    <input type="url" name="link" id="link" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($produtoEditar['link'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-secondary">Preço Estimado (R$)</label>
                    <input type="number" step="0.01" name="preco" id="preco" class="form-control" placeholder="1200.00" value="<?= htmlspecialchars($produtoEditar['preco'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-secondary">Loja / Marca</label>
                    <input type="text" name="loja" id="loja" class="form-control" placeholder="Ex: Amazon" value="<?= htmlspecialchars($produtoEditar['loja'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mt-4 text-end d-flex gap-2 justify-content-end">
                <?php if ($produtoEditar): ?>
                    <a href="index.php" class="btn btn-light rounded-3 px-4 fw-semibold text-secondary fs-5">Cancelar</a>
                    <button type="submit" class="btn-orange">
                        <i class="bi bi-check2 me-1"></i> Salvar Alterações
                    </button>
                <?php else: ?>
                    <button type="submit" class="btn-orange">
                        <i class="bi bi-check2 me-1"></i> Guardar na Lista
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- TABELA DE PRODUTOS -->
    <div class="app-card p-4">
        <h4 class="fw-bold mb-3 text-warning-emphasis"><i class="bi bi-list-stars me-2"></i> Itens Adicionados</h4>
        <div class="table-responsive">
            <table class="table align-middle border-0">
                <thead>
                    <tr class="text-muted border-bottom">
                        <th class="border-0 pb-3">PRODUTO</th>
                        <th class="border-0 pb-3">LOJA</th>
                        <th class="border-0 pb-3">VALOR</th>
                        <th class="border-0 pb-3">LINK</th>
                        <th class="border-0 pb-3">SITUAÇÃO</th>
                        <th class="border-0 pb-3 text-end">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produtos)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-emoji-smile fs-1 d-block mb-2 text-warning"></i>
                                Sua lista está vazia. Adicione o seu primeiro desejo no formulário acima!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produtos as $p): ?>
                            <tr class="border-bottom border-warning-subtle">
                                <td class="fw-bold py-3 text-dark"><?= htmlspecialchars($p['nome']) ?></td>
                                <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6"><?= htmlspecialchars($p['loja']) ?></span></td>
                                <td class="fw-bold text-dark">R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($p['link']) ?>" target="_blank" class="action-icon" title="Acessar Loja">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Ver loja
                                    </a>
                                </td>
                                <td>
                                    <?php if ($p['comprado']): ?>
                                        <span class="badge-status-bought"><i class="bi bi-check2-all me-1"></i>Comprado</span>
                                    <?php else: ?>
                                        <span class="badge-status-pending"><i class="bi bi-hourglass-split me-1"></i>Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="index.php?action=carregar_edicao&id=<?= $p['id'] ?>#formulario" class="action-icon text-warning-emphasis" title="Editar Item">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="index.php?action=toggle_status&id=<?= $p['id'] ?>" class="action-icon" title="Alternar Status">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </a>
                                    <a href="index.php?action=deletar&id=<?= $p['id'] ?>" class="action-icon delete" onclick="return confirm('Remover este item?')" title="Remover">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function validarFormulario() {
    const preco = document.getElementById('preco').value;
    const link = document.getElementById('link').value;

    if (preco <= 0) {
        alert('Por favor, informe um valor maior que zero.');
        return false;
    }

    if (!link.startsWith('http://') && !link.startsWith('https://')) {
        alert('O link deve ser um endereço válido (começar com http:// ou https://)');
        return false;
    }

    return true;
}
</script>

</body>
</html>