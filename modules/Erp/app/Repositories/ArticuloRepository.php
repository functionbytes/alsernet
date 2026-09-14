<?php

namespace Modules\Erp\Repositories;

class ArticuloRepository extends BaseOracleRepository
{
    protected string $table = 'articulo';

    protected string $primaryKey = 'idarticulo';

    protected function buildFilters(array $filters, array &$conditions, array &$params): void
    {
        if (! empty($filters['codigo'])) {
            $conditions[] = 't.codigo = :codigo';
            $params['codigo'] = $filters['codigo'];
        }

        if (! empty($filters['descripcion'])) {
            $conditions[] = 'UPPER(t.descripcion) LIKE UPPER(:descripcion)';
            $params['descripcion'] = '%'.$filters['descripcion'].'%';
        }

        if (! empty($filters['idmarca'])) {
            $conditions[] = 't.idmarca = :idmarca';
            $params['idmarca'] = $filters['idmarca'];
        }
    }

    protected function getDefaultWhereClause(): string
    {
        return 'WHERE t.fbaja IS NULL';
    }

    protected function getSelectColumns(): string
    {
        return 't.idarticulo, t.codigo, t.codbar, t.descripcion, '.
               't.preciomedio, t.precioultcompra, t.idmarca, t.idmodelo, '.
               't.estado, t.fcreacion, t.fmodificacion';
    }
}
