import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

interface CampaignEditorProps {
  campaignId?: number;
  campaign?: any;
  templates?: any[];
}

export function CampaignEditor({ campaignId, campaign, templates = [] }: CampaignEditorProps) {
  const [activeTab, setActiveTab] = useState<'content' | 'appearance' | 'conditions'>('content');
  const [content, setContent] = useState(campaign?.content || []);
  const [appearance, setAppearance] = useState(campaign?.appearance || {});
  const [conditions, setConditions] = useState(campaign?.conditions || []);
  const queryClient = useQueryClient();

  // Save campaign changes
  const saveMutation = useMutation({
    mutationFn: async (data: any) => {
      const response = await fetch(`/manager/helpdesk/campaigns/${campaignId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['campaign', campaignId] });
    },
  });

  const handleSave = () => {
    saveMutation.mutate({
      content,
      appearance,
      conditions,
    });
  };

  return (
    <div className="row">
      {/* Editor Tabs */}
      <div className="col-lg-8">
        <div className="card">
          <div className="card-header">
            <ul className="nav nav-tabs" role="tablist">
              <li className="nav-item">
                <button
                  className={`nav-link ${activeTab === 'content' ? 'active' : ''}`}
                  onClick={() => setActiveTab('content')}
                >
                  <i className="fa fa-grip"></i> Contenido
                </button>
              </li>
              <li className="nav-item">
                <button
                  className={`nav-link ${activeTab === 'appearance' ? 'active' : ''}`}
                  onClick={() => setActiveTab('appearance')}
                >
                  <i className="fa fa-palette"></i> Apariencia
                </button>
              </li>
              <li className="nav-item">
                <button
                  className={`nav-link ${activeTab === 'conditions' ? 'active' : ''}`}
                  onClick={() => setActiveTab('conditions')}
                >
                  <i className="fa fa-filter"></i> Condiciones
                </button>
              </li>
            </ul>
          </div>

          <div className="card-body">
            {activeTab === 'content' && <ContentEditorTab content={content} setContent={setContent} templates={templates} />}
            {activeTab === 'appearance' && <AppearanceEditorTab appearance={appearance} setAppearance={setAppearance} />}
            {activeTab === 'conditions' && <ConditionsEditorTab conditions={conditions} setConditions={setConditions} />}
          </div>

          <div className="card-footer">
            <button
              className="btn btn-primary"
              onClick={handleSave}
              disabled={saveMutation.isPending}
            >
              {saveMutation.isPending ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2"></span>
                  Guardando...
                </>
              ) : (
                <>
                  <i className="fa fa-save"></i> Guardar Cambios
                </>
              )}
            </button>
          </div>
        </div>
      </div>

      {/* Preview Sidebar */}
      <div className="col-lg-4">
        <div className="card sticky-top">
          <div className="card-header">
            <h5 className="mb-0">Vista Previa</h5>
          </div>
          <div className="card-body">
            <CampaignPreview content={content} appearance={appearance} />
          </div>
        </div>
      </div>
    </div>
  );
}

/**
 * Content Editor Tab
 */
function ContentEditorTab({ content, setContent, templates }: any) {
  return (
    <div>
      <div className="alert alert-info mb-3">
        <i className="fa fa-circle-info"></i>
        El editor de contenido permite agregar bloques de texto, imágenes y botones a tu campaña.
      </div>

      <div className="mb-3">
        <label className="form-label">Bloques de Contenido</label>
        <div className="border rounded p-3 bg-light mb-3">
          {content.length === 0 ? (
            <p className="text-muted mb-0">No hay bloques. Agrega el primer bloque usando el botón abajo.</p>
          ) : (
            <div className="list-group">
              {content.map((block: any, index: number) => (
                <div key={index} className="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <span className="badge bg-primary me-2">#{index + 1}</span>
                    <span className="text-capitalize">{block.type}</span>
                  </div>
                  <button
                    className="btn btn-sm btn-outline-danger"
                    onClick={() => setContent(content.filter((_: any, i: number) => i !== index))}
                  >
                    <i className="fa fa-trash"></i>
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="btn-group w-100" role="group">
          <button
            className="btn btn-outline-primary"
            onClick={() => setContent([...content, { type: 'text', value: 'Nuevo texto' }])}
          >
            <i className="fa fa-text"></i> Texto
          </button>
          <button
            className="btn btn-outline-primary"
            onClick={() => setContent([...content, { type: 'image', url: '' }])}
          >
            <i className="fa fa-image"></i> Imagen
          </button>
          <button
            className="btn btn-outline-primary"
            onClick={() => setContent([...content, { type: 'button', label: 'Click aquí' }])}
          >
            <i className="fa fa-square"></i> Botón
          </button>
        </div>
      </div>
    </div>
  );
}

/**
 * Appearance Editor Tab
 */
function AppearanceEditorTab({ appearance, setAppearance }: any) {
  const handleColorChange = (key: string, value: string) => {
    setAppearance({ ...appearance, [key]: value });
  };

  return (
    <div>
      <div className="row g-3">
        <div className="col-md-6">
          <label className="form-label">Color de Fondo</label>
          <div className="input-group">
            <input
              type="color"
              className="form-control form-control-color"
              value={appearance.background_color || '#ffffff'}
              onChange={(e) => handleColorChange('background_color', e.target.value)}
            />
            <input
              type="text"
              className="form-control"
              value={appearance.background_color || '#ffffff'}
              onChange={(e) => handleColorChange('background_color', e.target.value)}
            />
          </div>
        </div>

        <div className="col-md-6">
          <label className="form-label">Color de Texto</label>
          <div className="input-group">
            <input
              type="color"
              className="form-control form-control-color"
              value={appearance.text_color || '#000000'}
              onChange={(e) => handleColorChange('text_color', e.target.value)}
            />
            <input
              type="text"
              className="form-control"
              value={appearance.text_color || '#000000'}
              onChange={(e) => handleColorChange('text_color', e.target.value)}
            />
          </div>
        </div>

        <div className="col-md-6">
          <label className="form-label">Color Primario</label>
          <div className="input-group">
            <input
              type="color"
              className="form-control form-control-color"
              value={appearance.primary_color || '#90bb13'}
              onChange={(e) => handleColorChange('primary_color', e.target.value)}
            />
            <input
              type="text"
              className="form-control"
              value={appearance.primary_color || '#90bb13'}
              onChange={(e) => handleColorChange('primary_color', e.target.value)}
            />
          </div>
        </div>

        <div className="col-md-6">
          <label className="form-label">Esquina de Ancho</label>
          <input
            type="range"
            className="form-range"
            min="0"
            max="20"
            value={appearance.border_radius || '0'}
            onChange={(e) => handleColorChange('border_radius', e.target.value)}
          />
          <small className="text-muted">{appearance.border_radius || '0'}px</small>
        </div>
      </div>
    </div>
  );
}

/**
 * Conditions Editor Tab
 */
function ConditionsEditorTab({ conditions, setConditions }: any) {
  const addCondition = () => {
    setConditions([...conditions, { field: 'page_url', operator: 'contains', value: '' }]);
  };

  const removeCondition = (index: number) => {
    setConditions(conditions.filter((_: any, i: number) => i !== index));
  };

  return (
    <div>
      <p className="text-muted mb-3">
        Configura las condiciones para mostrar esta campaña solo a ciertos visitantes.
      </p>

      <div className="mb-3">
        {conditions.length === 0 ? (
          <p className="text-muted">Sin condiciones (mostrar a todos)</p>
        ) : (
          <div className="space-y-2">
            {conditions.map((condition: any, index: number) => (
              <div key={index} className="row g-2 align-items-end">
                <div className="col-md-4">
                  <select
                    className="form-select form-select-sm"
                    value={condition.field}
                    onChange={(e) => {
                      const updated = [...conditions];
                      updated[index].field = e.target.value;
                      setConditions(updated);
                    }}
                  >
                    <option value="page_url">URL de Página</option>
                    <option value="device">Dispositivo</option>
                    <option value="country">País</option>
                  </select>
                </div>
                <div className="col-md-3">
                  <select
                    className="form-select form-select-sm"
                    value={condition.operator}
                    onChange={(e) => {
                      const updated = [...conditions];
                      updated[index].operator = e.target.value;
                      setConditions(updated);
                    }}
                  >
                    <option value="contains">Contiene</option>
                    <option value="equals">Es igual a</option>
                    <option value="starts">Comienza con</option>
                  </select>
                </div>
                <div className="col-md-4">
                  <input
                    type="text"
                    className="form-control form-control-sm"
                    value={condition.value}
                    onChange={(e) => {
                      const updated = [...conditions];
                      updated[index].value = e.target.value;
                      setConditions(updated);
                    }}
                  />
                </div>
                <div className="col-md-1">
                  <button className="btn btn-sm btn-outline-danger" onClick={() => removeCondition(index)}>
                    <i className="fa fa-trash"></i>
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <button className="btn btn-sm btn-outline-primary" onClick={addCondition}>
        <i className="fa fa-plus"></i> Agregar Condición
      </button>
    </div>
  );
}

/**
 * Campaign Preview Component
 */
function CampaignPreview({ content, appearance }: any) {
  const bgColor = appearance.background_color || '#ffffff';
  const textColor = appearance.text_color || '#000000';

  return (
    <div className="bg-light p-3 rounded border" style={{ backgroundColor: bgColor, color: textColor }}>
      <div className="text-center mb-3">
        <h6 className="mb-0">Vista Previa</h6>
        <small className="text-muted">La campaña se vería así:</small>
      </div>

      <div className="rounded p-3" style={{ backgroundColor: bgColor, minHeight: '300px' }}>
        {content.length === 0 ? (
          <p className="text-muted text-center mb-0">Sin contenido aún</p>
        ) : (
          <div>
            {content.map((block: any, index: number) => (
              <div key={index} className="mb-3">
                {block.type === 'text' && <p className="mb-2">{block.value}</p>}
                {block.type === 'image' && (
                  <img
                    src={block.url || 'https://via.placeholder.com/300x150'}
                    className="img-fluid mb-2"
                    alt="Preview"
                  />
                )}
                {block.type === 'button' && (
                  <button className="btn btn-sm btn-primary">{block.label}</button>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
