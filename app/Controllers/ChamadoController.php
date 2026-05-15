<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ChamadoRepository;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\CsrfService;
use App\Services\SecurityService;

final class ChamadoController extends Controller
{
    private ChamadoRepository $chamados;

    public function __construct()
    {
        AuthService::exigirLogin();
        $this->chamados = new ChamadoRepository();
    }

    public function index(): void
    {
        $lista = $this->chamados->listarPorUsuario(AuthService::id());
        $this->view('chamados/index', ['titulo' => 'Meus chamados', 'chamados' => $lista]);
    }

    public function criarForm(): void
    {
        $this->view('chamados/form', ['titulo' => 'Novo chamado', 'chamado' => null, 'acao' => '/chamados/criar']);
    }

    public function criar(): void
    {
        $this->validarCsrf('/chamados/novo');
        $dados = $this->validarDados('/chamados/novo');
        $id = $this->chamados->criar(AuthService::id(), $dados);
        AuditService::registrar(AuthService::id(), 'CRIAR_CHAMADO', 'Chamado #' . $id);
        flash('sucesso', 'Chamado criado com sucesso.');
        redirect('/chamados');
    }

    public function editarForm(int $id): void
    {
        $chamado = $this->chamados->buscarDoUsuario($id, AuthService::id());
        if (!$chamado) {
            flash('erro', 'Chamado não encontrado ou sem permissão.');
            redirect('/chamados');
        }
        $this->view('chamados/form', ['titulo' => 'Editar chamado', 'chamado' => $chamado, 'acao' => '/chamados/editar?id=' . $id]);
    }

    public function atualizar(int $id): void
    {
        $this->validarCsrf('/chamados/editar?id=' . $id);
        $dados = $this->validarDados('/chamados/editar?id=' . $id);
        $this->chamados->atualizar($id, AuthService::id(), $dados);
        AuditService::registrar(AuthService::id(), 'EDITAR_CHAMADO', 'Chamado #' . $id);
        flash('sucesso', 'Chamado atualizado com sucesso.');
        redirect('/chamados');
    }

    public function remover(int $id): void
    {
        $this->validarCsrf('/chamados');
        $this->chamados->remover($id, AuthService::id());
        AuditService::registrar(AuthService::id(), 'REMOVER_CHAMADO', 'Chamado #' . $id);
        flash('sucesso', 'Chamado removido com sucesso.');
        redirect('/chamados');
    }

    private function validarCsrf(string $retorno): void
    {
        if (!CsrfService::validate($_POST['csrf_token'] ?? null)) {
            flash('erro', 'Token de segurança inválido.');
            redirect($retorno);
        }
    }

    private function validarDados(string $retorno): array
    {
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $prioridade = $_POST['prioridade'] ?? 'Media';
        $status = $_POST['status'] ?? 'Aberto';
        $prioridades = ['Baixa', 'Media', 'Alta', 'Critica'];
        $statusValidos = ['Aberto', 'Em andamento', 'Resolvido', 'Cancelado'];

        $erros = [];
        if (!SecurityService::validarTexto($titulo, 5, 160)) {
            $erros[] = 'O título deve ter entre 5 e 160 caracteres.';
        }
        if (!SecurityService::validarTexto($descricao, 10, 5000)) {
            $erros[] = 'A descrição deve ter entre 10 e 5000 caracteres.';
        }
        if (!in_array($prioridade, $prioridades, true)) {
            $erros[] = 'Prioridade inválida.';
        }
        if (!in_array($status, $statusValidos, true)) {
            $erros[] = 'Status inválido.';
        }

        if ($erros) {
            foreach ($erros as $erro) {
                flash('erro', $erro);
            }
            $_SESSION['old'] = $_POST;
            redirect($retorno);
        }

        return compact('titulo', 'descricao', 'prioridade', 'status');
    }
}
