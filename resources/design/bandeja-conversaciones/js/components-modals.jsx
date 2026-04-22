/* ═══════════════ MODALES ═══════════════ */
const Modal = ({ open, onClose, title, icon, width=480, children, footer }) => {
  React.useEffect(()=>{
    if(!open) return;
    const h = (e)=>{ if(e.key==='Escape') onClose(); };
    window.addEventListener('keydown', h);
    return ()=>window.removeEventListener('keydown', h);
  },[open,onClose]);
  if(!open) return null;
  return (
    <div className="modal-scrim" onMouseDown={onClose}>
      <div className="modal" style={{width}} onMouseDown={e=>e.stopPropagation()}>
        <div className="modal-head">
          <div className="t"><i className={`fa-solid ${icon}`}></i>{title}</div>
          <button className="x" onClick={onClose}><i className="fa-solid fa-xmark"></i></button>
        </div>
        <div className="modal-body">{children}</div>
        {footer && <div className="modal-foot">{footer}</div>}
      </div>
    </div>
  );
};

const AssignModal = ({ open, onClose }) => {
  const [q, setQ] = React.useState('');
  const [pick, setPick] = React.useState(1);
  const agents = CONV_DATA.AGENTS.filter(a=>a.name.toLowerCase().includes(q.toLowerCase()));
  return (
    <Modal open={open} onClose={onClose} title="Asignar conversación" icon="fa-user-check" width={460}
      footer={<>
        <span className="hint"><span className="kbd">↵</span> asignar · <span className="kbd">esc</span> cancelar</span>
        <div style={{marginLeft:'auto',display:'flex',gap:8}}>
          <button className="btn" onClick={onClose}>Cancelar</button>
          <button className="btn primary" onClick={onClose}><i className="fa-solid fa-user-check"></i>Asignar</button>
        </div>
      </>}>
      <div className="modal-search">
        <i className="fa-solid fa-magnifying-glass"></i>
        <input autoFocus placeholder="Buscar agente o equipo…" value={q} onChange={e=>setQ(e.target.value)}/>
      </div>
      <div className="modal-section-label">Equipos</div>
      <div className="modal-pickrow">
        <div className="logo" style={{background:'#eef2ff',color:'#4f46e5'}}><i className="fa-solid fa-users"></i></div>
        <div className="body"><div className="nm">Soporte N1</div><div className="s">5 agentes · 12 abiertas</div></div>
        <i className="fa-solid fa-chevron-right" style={{color:'var(--text-muted)',fontSize:10}}></i>
      </div>
      <div className="modal-pickrow">
        <div className="logo" style={{background:'#f0fdf4',color:'#16a34a'}}><i className="fa-solid fa-headset"></i></div>
        <div className="body"><div className="nm">Soporte N2</div><div className="s">3 agentes · 4 abiertas</div></div>
        <i className="fa-solid fa-chevron-right" style={{color:'var(--text-muted)',fontSize:10}}></i>
      </div>
      <div className="modal-section-label">Agentes</div>
      {agents.map(a=>(
        <div key={a.id} className={`modal-pickrow ${pick===a.id?'on':''}`} onClick={()=>setPick(a.id)}>
          <span className={`assign-dot ${a.color}`} style={{width:28,height:28,fontSize:10}}>{a.initials}</span>
          <div className="body"><div className="nm">{a.name}</div><div className="s">{a.role} · {a.online==='on'?'En línea':a.online==='away'?'Ausente':'Offline'}</div></div>
          <span className={`presence ${a.online}`}></span>
          {pick===a.id && <i className="fa-solid fa-check" style={{color:'var(--accent)',marginLeft:4}}></i>}
        </div>
      ))}
      <div className="modal-divider"></div>
      <label className="modal-check">
        <input type="checkbox" defaultChecked/>
        <span>Notificar al agente por email</span>
      </label>
      <label className="modal-check">
        <input type="checkbox"/>
        <span>Transferir también conversaciones anteriores</span>
      </label>
    </Modal>
  );
};

const CloseModal = ({ open, onClose }) => {
  const [r, setR] = React.useState('resolved');
  const reasons = [
    { id:'resolved', t:'Resuelto', s:'El problema del cliente ha sido solucionado', i:'fa-circle-check', c:'var(--success)' },
    { id:'duplicated', t:'Duplicado', s:'Esta conversación ya existe en otro hilo', i:'fa-clone', c:'var(--text-muted)' },
    { id:'spam', t:'Spam / no procede', s:'Mensaje no solicitado o fuera de contexto', i:'fa-ban', c:'var(--danger)' },
    { id:'unresponsive', t:'Sin respuesta del cliente', s:'Cerrar por inactividad', i:'fa-user-slash', c:'var(--warning)' },
    { id:'other', t:'Otro motivo', s:'', i:'fa-ellipsis', c:'var(--text-muted)' },
  ];
  return (
    <Modal open={open} onClose={onClose} title="Cerrar conversación" icon="fa-check" width={520}
      footer={<>
        <span className="hint">Se enviará una encuesta de satisfacción automáticamente</span>
        <div style={{marginLeft:'auto',display:'flex',gap:8}}>
          <button className="btn" onClick={onClose}>Cancelar</button>
          <button className="btn primary" onClick={onClose}><i className="fa-solid fa-check"></i>Cerrar conversación</button>
        </div>
      </>}>
      <div className="modal-section-label">Motivo de cierre</div>
      <div style={{display:'grid',gap:6}}>
        {reasons.map(x=>(
          <div key={x.id} className={`modal-radio-card ${r===x.id?'on':''}`} onClick={()=>setR(x.id)}>
            <div className="icon" style={{color:x.c}}><i className={`fa-solid ${x.i}`}></i></div>
            <div className="body"><div className="t">{x.t}</div>{x.s && <div className="s">{x.s}</div>}</div>
            <div className={`radio ${r===x.id?'on':''}`}></div>
          </div>
        ))}
      </div>
      <div className="modal-section-label" style={{marginTop:14}}>Nota de cierre (opcional, visible para el equipo)</div>
      <textarea className="modal-ta" placeholder="Ej: Cliente confirmó recepción del pedido reenviado el 18/04 a las 16:30."></textarea>
      <label className="modal-check" style={{marginTop:6}}>
        <input type="checkbox" defaultChecked/>
        <span>Enviar encuesta CSAT al cliente</span>
      </label>
    </Modal>
  );
};

const TagsModal = ({ open, onClose }) => {
  const [sel, setSel] = React.useState(new Set(['ur','env']));
  const toggle = (id)=>{
    const n = new Set(sel);
    n.has(id) ? n.delete(id) : n.add(id);
    setSel(n);
  };
  return (
    <Modal open={open} onClose={onClose} title="Etiquetas" icon="fa-tag" width={420}
      footer={<>
        <button className="btn" onClick={onClose}>Cancelar</button>
        <button className="btn primary" onClick={onClose}>Aplicar ({sel.size})</button>
      </>}>
      <div className="modal-search"><i className="fa-solid fa-magnifying-glass"></i><input autoFocus placeholder="Buscar o crear etiqueta…"/></div>
      <div className="modal-section-label">Etiquetas disponibles</div>
      <div style={{display:'flex',flexWrap:'wrap',gap:6}}>
        {CONV_DATA.TAGS.map(t=>(
          <button key={t.id} className={`modal-tag-pick ${sel.has(t.id)?'on':''}`} onClick={()=>toggle(t.id)}>
            <span className="d" style={{background:t.color}}></span>
            {t.label}
            {sel.has(t.id) && <i className="fa-solid fa-check" style={{fontSize:9,marginLeft:2}}></i>}
          </button>
        ))}
        <button className="modal-tag-pick add"><i className="fa-solid fa-plus" style={{fontSize:9}}></i>Nueva</button>
      </div>
    </Modal>
  );
};

const MergeModal = ({ open, onClose }) => {
  const candidates = [
    { id:7801, ch:'facebook', t:'Consulta de pedido', prev:'Hola, quería saber si llegó ya mi pedido…', time:'hace 4 días' },
    { id:7654, ch:'widget', t:'Formulario web', prev:'Buenas tardes, tengo una duda sobre…', time:'12 mar' },
  ];
  const [p, setP] = React.useState(7801);
  return (
    <Modal open={open} onClose={onClose} title="Fusionar con otra conversación" icon="fa-code-merge" width={560}
      footer={<>
        <span className="hint">El historial se combinará en orden cronológico</span>
        <div style={{marginLeft:'auto',display:'flex',gap:8}}>
          <button className="btn" onClick={onClose}>Cancelar</button>
          <button className="btn primary" onClick={onClose}><i className="fa-solid fa-code-merge"></i>Fusionar</button>
        </div>
      </>}>
      <div style={{padding:'10px 12px',background:'#fef3c7',border:'1px solid #fde68a',borderRadius:8,fontSize:11.5,color:'#78350f',display:'flex',gap:8,alignItems:'flex-start',marginBottom:12}}>
        <i className="fa-solid fa-triangle-exclamation" style={{marginTop:2}}></i>
        <div>Esta acción <b>no se puede deshacer</b>. La conversación secundaria se archivará y todos sus mensajes aparecerán en la principal.</div>
      </div>
      <div className="modal-section-label">Conversaciones del mismo contacto</div>
      {candidates.map(c=>(
        <div key={c.id} className={`modal-pickrow ${p===c.id?'on':''}`} onClick={()=>setP(c.id)}>
          <div className="logo" style={{background:c.ch==='facebook'?'var(--fb)':'var(--widget)',color:'#fff'}}>
            <i className={c.ch==='facebook'?'fa-brands fa-facebook-messenger':'fa-regular fa-comment'}></i>
          </div>
          <div className="body">
            <div className="nm">#{c.id} · {c.t}</div>
            <div className="s" style={{whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',maxWidth:340}}>{c.prev} · {c.time}</div>
          </div>
          <div className={`radio ${p===c.id?'on':''}`}></div>
        </div>
      ))}
    </Modal>
  );
};

const HSMModal = ({ open, onClose }) => {
  const tpls = [
    { id:1, n:'Confirmación de pedido', cat:'Utilidad', st:'approved', body:'Hola {{1}}, tu pedido #{{2}} ha sido confirmado. Total: {{3}}. Gracias por tu compra.' },
    { id:2, n:'Envío en camino', cat:'Utilidad', st:'approved', body:'Tu pedido #{{1}} está en camino. Seguimiento: {{2}}. Llegará aprox. el {{3}}.' },
    { id:3, n:'Recordatorio de cita', cat:'Utilidad', st:'approved', body:'Hola {{1}}, te recordamos tu cita el {{2}} a las {{3}}. Responde CONFIRMAR o CANCELAR.' },
    { id:4, n:'Encuesta CSAT', cat:'Marketing', st:'review', body:'¡Gracias {{1}}! ¿Cómo valorarías tu experiencia del 1 al 5?' },
  ];
  const [sel, setSel] = React.useState(1);
  const tpl = tpls.find(t=>t.id===sel);
  return (
    <Modal open={open} onClose={onClose} title="Plantillas WhatsApp (HSM)" icon="fa-file-lines" width={680}
      footer={<>
        <button className="btn" onClick={onClose}>Cancelar</button>
        <button className="btn primary" onClick={onClose}><i className="fa-regular fa-paper-plane"></i>Insertar plantilla</button>
      </>}>
      <div style={{display:'grid',gridTemplateColumns:'260px 1fr',gap:12}}>
        <div>
          <div className="modal-search" style={{marginBottom:8}}><i className="fa-solid fa-magnifying-glass"></i><input placeholder="Buscar…"/></div>
          {tpls.map(t=>(
            <div key={t.id} className={`modal-pickrow ${sel===t.id?'on':''}`} onClick={()=>setSel(t.id)} style={{padding:'8px 10px'}}>
              <div className="body">
                <div className="nm" style={{fontSize:11.5}}>{t.n}</div>
                <div className="s" style={{display:'flex',gap:6,alignItems:'center'}}>
                  <span className={`status-chip ${t.st}`}>{t.st==='approved'?'Aprobada':'En revisión'}</span>
                  <span>{t.cat}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
        <div className="tpl-preview">
          <div className="tpl-head">
            <span>Vista previa</span>
            <span className={`status-chip ${tpl.st}`}>{tpl.st==='approved'?'Aprobada':'En revisión'}</span>
          </div>
          <div className="tpl-chat">
            <div className="wa-bubble">{tpl.body}</div>
          </div>
          <div className="modal-section-label" style={{marginTop:4}}>Variables</div>
          {[...tpl.body.matchAll(/\{\{(\d+)\}\}/g)].map((m,i)=>(
            <div key={i} className="tpl-var">
              <span className="k">{`{{${m[1]}}}`}</span>
              <input placeholder={['Nombre del cliente','Número de pedido','Valor','Fecha'][i]}/>
            </div>
          ))}
        </div>
      </div>
    </Modal>
  );
};

const ShortcutsModal = ({ open, onClose }) => (
  <Modal open={open} onClose={onClose} title="Atajos de teclado" icon="fa-keyboard" width={600}>
    <div className="kb-grid">
      {[
        ['Navegación',[
          ['↑ ↓', 'Conversación anterior / siguiente'],
          ['J / K', 'Siguiente / anterior (vim)'],
          ['⌘ K', 'Buscar en todo'],
          ['G → U', 'Ir a sin leer'],
          ['G → M', 'Ir a mías'],
        ]],
        ['Acciones',[
          ['R', 'Responder'],
          ['N', 'Nota interna'],
          ['A', 'Asignar'],
          ['T', 'Añadir etiqueta'],
          ['E', 'Archivar'],
          ['#', 'Cerrar conversación'],
          ['⇧ U', 'Marcar como no leída'],
          ['S', 'Snooze / posponer'],
        ]],
        ['Composer',[
          ['/', 'Respuestas rápidas'],
          ['@', 'Mencionar a un compañero'],
          ['⌘ ↵', 'Enviar'],
          ['⌘ J', 'Sugerencia de IA'],
          ['⌘ ⇧ A', 'Adjuntar archivo'],
        ]],
        ['General',[
          ['⌘ \\', 'Mostrar/ocultar panel derecho'],
          ['⌘ ,', 'Preferencias'],
          ['?', 'Esta ayuda'],
          ['esc', 'Cerrar modal'],
        ]],
      ].map(([grp,items])=>(
        <div key={grp}>
          <div className="kb-grp">{grp}</div>
          {items.map(([k,d])=>(
            <div key={k} className="kb-row">
              <span className="kb-keys">{k.split(' ').map((key,i)=><span key={i} className="kbd">{key}</span>)}</span>
              <span className="kb-desc">{d}</span>
            </div>
          ))}
        </div>
      ))}
    </div>
  </Modal>
);

/* ═══════════════ EMPTY STATE ═══════════════ */
const EmptyThread = () => (
  <div className="empty-thread">
    <div className="empty-ill">
      <div className="ring r1"></div>
      <div className="ring r2"></div>
      <div className="ring r3"></div>
      <i className="fa-regular fa-comments"></i>
    </div>
    <div className="empty-t">Selecciona una conversación</div>
    <div className="empty-s">Elige un mensaje de la lista para empezar a responder, o usa <span className="kbd">⌘ K</span> para buscar.</div>
    <div className="empty-tips">
      <div className="tip"><span className="kbd">R</span><span>Responder rápido</span></div>
      <div className="tip"><span className="kbd">A</span><span>Asignar</span></div>
      <div className="tip"><span className="kbd">#</span><span>Cerrar</span></div>
      <div className="tip"><span className="kbd">?</span><span>Todos los atajos</span></div>
    </div>
  </div>
);

Object.assign(window, { Modal, AssignModal, CloseModal, TagsModal, MergeModal, HSMModal, ShortcutsModal, EmptyThread });
