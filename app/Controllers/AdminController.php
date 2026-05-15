<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ChamadoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\CsrfService;

final class AdminController extends Controller
{
    private ChamadoRepository $chamados;
    private UsuarioRepository  $usuarios;

    public function __construct()
    {
        AuthService::exigirAdmin();
        $this->chamados = new ChamadoRepository();
        $this->usuarios = new UsuarioRepository();
    }

    /** Painel: lista todas as empresas com estatísticas */
    public function index(): void
    {
        $empresas = $this->usuarios->listarEmpresasComEstatisticas();
        $this->view('admin/empresas', [
            'titulo'   => 'Painel Admin — Empresas',
            'empresas' => $empresas,
        ]);
    }

    /** Lista chamados de uma empresa específica */
    public function chamadosEmpresa(): void
    {
        $empresaId = (int) ($_GET['empresa_id'] ?? 0);
        $empresa   = $this->usuarios->empresaPorId($empresaId);
        if (!$empresa) {
            flash('erro', 'Empresa não encontrada.');
            redirect('/admin');
        }

        $chamados = $this->chamados->listarPorEmpresa($empresaId);
        $this->view('admin/chamados', [
            'titulo'   => 'Chamados — ' . $empresa['nome'],
            'empresa'  => $empresa,
            'chamados' => $chamados,
        ]);
    }

    /** Altera o status de um chamado (ação do admin) */
    public function atualizarStatus(): void
    {
        if (!CsrfService::validate($_POST['csrf_token'] ?? null)) {
            flash('erro', 'Token de segurança inválido.');
            redirect('/admin');
        }

        $id         = (int) ($_POST['id'] ?? 0);
        $status     = $_POST['status'] ?? '';
        $empresaId  = (int) ($_POST['empresa_id'] ?? 0);
        $statusValidos = ['Aberto', 'Em andamento', 'Resolvido', 'Cancelado'];

        if (!in_array($status, $statusValidos, true)) {
            flash('erro', 'Status inválido.');
            redirect('/admin/chamados?empresa_id=' . $empresaId);
        }

        $chamado = $this->chamados->buscarPorId($id);
        if (!$chamado) {
            flash('erro', 'Chamado não encontrado.');
            redirect('/admin/chamados?empresa_id=' . $empresaId);
        }

        $this->chamados->atualizarStatus($id, $status);
        AuditService::registrar(AuthService::id(), 'ADMIN_ATUALIZAR_STATUS', "Chamado #$id → $status");
        flash('sucesso', "Status do chamado #{$id} alterado para \"{$status}\".");
        redirect('/admin/chamados?empresa_id=' . $empresaId);
    }
}
