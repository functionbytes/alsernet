/* ═══════════════ COMPOSER v2 — HSM, translate, attach ═══════════════ */
const HSMPickerInline = ({ onInsert, onClose }) => {
  const tpls = CONV_DATA.CANNED; // fallback to canned for display
  const templates = [
    { id:1, n:'Confirmación de pedido', cat:'Utilidad', st:'approved',
      body:'Hola {{nombre}}, tu pedido #{{numero}} ha sido confirmado. Total: {{total}}. Gracias por tu compra.' },
    { id:2, n:'Envío en camino', cat:'Utilidad', st:'approved',
      body:'Tu pedido #{{numero}} está en camino. Seguimiento: {{tracking}}. Llegará aprox. el {{fecha}}.' },
    { id:3, n:'Recordatorio de cita', cat:'Utilidad', st:'approved',
      body:'Hola {{nombre}}, te recordamos tu cita el {{fecha}} a las {{hora}}. Responde CONFIRMAR o CANCELAR.' },
    { id:4, n:'Encuesta CSAT', cat:'Marketing', st:'review',
      body:'¡Gracias {{nombre}}! ¿Cómo valorarías tu experiencia del 1 al 5?' },
    { id:5, n:'Reactivación 24h', cat:'Utilidad', st:'approved',
      body:'Hola {{nombre}}, la ventana de 24h está por expirar. Si necesitas algo más, dinos — estaremos encantados de ayudarte.' },
  ];
  const [sel, setSel] = React.useState(1);
  const [vars, setVars] = React.useState({});
  const tpl = templates.find(t=>t.id===sel);
  const varNames = [...(tpl.body.matchAll(/\{\{(\w+)\}\}/g))].map(m=>m[1]);
  const preview = tpl.body.replace(/\{\{(\w+)\}\}/g, (_, k)=> vars[k] ? `<b>${vars[k]}</b>` : `<span class="tpl-ph">{{${k}}}</span>`);

  const insert = ()=>{
    const txt = tpl.body.replace(/\{\{(\w+)\}\}/g, (_,k)=>vars[k]||`{{${k}}}`);
    onInsert(txt);
  };

  return (
    <div className="hsm-picker" onClick={e=>e.stopPropagation()}>
      <div className="hsm-head">
        <i className="fa-solid fa-file-lines"></i>
        <span>Plantillas WhatsApp (HSM)</span>
        <button onClick={onClose}><i className="fa-solid fa-xmark"></i></button>
      </div>
      <div className="hsm-body">
        {/* left list */}
        <div className="hsm-list">
          <div className="hsm-search">
            <i className="fa-solid fa-magnifying-glass"></i>
            <input placeholder="Buscar…"/>
          </div>
          {templates.map(t=>(
            <div key={t.id} className={`hsm-row ${sel===t.id?'on':''}`} onClick={()=>setSel(t.id)}>
              <div className="nm">{t.n}</div>
              <div className="meta">
                <span className={`status-chip ${t.st}`}>{t.st==='approved'?'Aprobada':'Revisión'}</span>
                <span>{t.cat}</span>
              </div>
            </div>
          ))}
        </div>
        {/* right preview + vars */}
        <div className="hsm-detail">
          <div className="hsm-preview-label">Vista previa</div>
          <div className="hsm-chat">
            <div className="wa-bubble" dangerouslySetInnerHTML={{__html:preview}}></div>
          </div>
          {varNames.length>0 && (
            <>
              <div className="hsm-preview-label" style={{marginTop:10}}>Variables</div>
              <div className="hsm-vars">
                {varNames.map(v=>(
                  <div key={v} className="tpl-var">
                    <span className="k">{`{{${v}}}`}</span>
                    <input placeholder={v.charAt(0).toUpperCase()+v.slice(1)}
                      value={vars[v]||''} onChange={e=>setVars(s=>({...s,[v]:e.target.value}))}/>
                  </div>
                ))}
              </div>
            </>
          )}
          <div className="hsm-foot">
            <button className="btn" onClick={onClose}>Cancelar</button>
            <button className="btn primary" onClick={insert}><i className="fa-regular fa-paper-plane"></i>Insertar</button>
          </div>
        </div>
      </div>
    </div>
  );
};

const TranslatePanel = ({ onClose }) => {
  const [src, setSrc] = React.useState('es');
  const [dst, setDst] = React.useState('en');
  const [mode, setMode] = React.useState('incoming'); // incoming | outgoing | both
  const langs = [
    {id:'es',l:'Español',flag:'🇪🇸'},{id:'en',l:'Inglés',flag:'🇬🇧'},
    {id:'fr',l:'Francés',flag:'🇫🇷'},{id:'pt',l:'Portugués',flag:'🇵🇹'},
    {id:'de',l:'Alemán',flag:'🇩🇪'},{id:'it',l:'Italiano',flag:'🇮🇹'},
    {id:'ar',l:'Árabe',flag:'🇸🇦'},{id:'zh',l:'Chino',flag:'🇨🇳'},
  ];
  return (
    <div className="translate-panel" onClick={e=>e.stopPropagation()}>
      <div className="tp-head">
        <i className="fa-solid fa-language"></i>
        <span>Traducción de conversación</span>
        <button onClick={onClose}><i className="fa-solid fa-xmark"></i></button>
      </div>
      <div className="tp-body">
        <div className="tp-section">
          <div className="tp-lbl">Modo de traducción</div>
          <div className="tp-modes">
            {[
              {id:'incoming', t:'Mensajes entrantes', s:'Traducir lo que dice el cliente', i:'fa-arrow-right-to-bracket'},
              {id:'outgoing', t:'Mensajes salientes', s:'Traducir mis respuestas antes de enviar', i:'fa-arrow-right-from-bracket'},
              {id:'both', t:'Ambas direcciones', s:'Traducción completa bidireccional', i:'fa-arrows-left-right'},
            ].map(m=>(
              <div key={m.id} className={`tp-mode ${mode===m.id?'on':''}`} onClick={()=>setMode(m.id)}>
                <i className={`fa-solid ${m.i}`}></i>
                <div>
                  <div className="t">{m.t}</div>
                  <div className="s">{m.s}</div>
                </div>
                <div className={`radio ${mode===m.id?'on':''}`}></div>
              </div>
            ))}
          </div>
        </div>
        <div className="tp-pair">
          <div className="tp-section" style={{flex:1}}>
            <div className="tp-lbl">Idioma del cliente</div>
            <select className="tp-sel" value={src} onChange={e=>setSrc(e.target.value)}>
              {langs.map(l=><option key={l.id} value={l.id}>{l.flag} {l.l}</option>)}
            </select>
          </div>
          <i className="fa-solid fa-arrows-left-right" style={{color:'var(--text-muted)',fontSize:12,marginTop:28}}></i>
          <div className="tp-section" style={{flex:1}}>
            <div className="tp-lbl">Tu idioma</div>
            <select className="tp-sel" value={dst} onChange={e=>setDst(e.target.value)}>
              {langs.map(l=><option key={l.id} value={l.id}>{l.flag} {l.l}</option>)}
            </select>
          </div>
        </div>
        <label className="modal-check" style={{marginTop:4}}>
          <input type="checkbox" defaultChecked/>
          <span>Mostrar texto original junto a la traducción</span>
        </label>
        <label className="modal-check">
          <input type="checkbox"/>
          <span>Notificar al cliente que se está usando traducción automática</span>
        </label>
      </div>
      <div className="tp-foot">
        <button className="btn" onClick={onClose}>Cancelar</button>
        <button className="btn primary" onClick={onClose}><i className="fa-solid fa-language"></i>Activar traducción</button>
      </div>
    </div>
  );
};

const AttachMenu = ({ onClose, anchor }) => {
  const opts = [
    { id:'file', i:'fa-regular fa-file', lbl:'Documento', sub:'PDF, DOC, XLS…', accept:'.pdf,.doc,.docx,.xls,.xlsx' },
    { id:'image', i:'fa-regular fa-image', lbl:'Imagen', sub:'JPG, PNG, GIF, WebP', accept:'image/*' },
    { id:'audio', i:'fa-solid fa-microphone', lbl:'Audio / nota de voz', sub:'MP3, OGG, M4A' },
    { id:'video', i:'fa-solid fa-video', lbl:'Video', sub:'MP4, MOV' },
    { id:'contact', i:'fa-regular fa-address-card', lbl:'Contacto', sub:'Compartir ficha vCard' },
    { id:'location', i:'fa-solid fa-location-dot', lbl:'Ubicación', sub:'Google Maps o coordenadas' },
  ];
  return (
    <div className="attach-menu" onClick={e=>e.stopPropagation()}>
      {opts.map(o=>(
        <button key={o.id} className="attach-row" onClick={onClose}>
          <div className="ico"><i className={`fa-solid ${o.i}`}></i></div>
          <div className="body">
            <div className="t">{o.lbl}</div>
            <div className="s">{o.sub}</div>
          </div>
        </button>
      ))}
      <div className="attach-limit">
        <i className="fa-solid fa-circle-info"></i>
        Tamaño máx: 16 MB · WhatsApp Business
      </div>
    </div>
  );
};

const Composer = ({ replyTo, onClearReply, internal, onInternal }) => {
  const [openCanned, setOpenCanned] = React.useState(false);
  const [showHSM, setShowHSM] = React.useState(false);
  const [showTranslate, setShowTranslate] = React.useState(false);
  const [showAttach, setShowAttach] = React.useState(false);
  const [ai, setAi] = React.useState(false);
  const [text, setText] = React.useState('');
  const ref = React.useRef(null);

  // Close all popups when clicking outside
  React.useEffect(()=>{
    const h = (e)=>{
      if(!e.target.closest('.hsm-picker')) setShowHSM(false);
      if(!e.target.closest('.translate-panel') && !e.target.closest('.ctb-lang')) setShowTranslate(false);
      if(!e.target.closest('.attach-menu') && !e.target.closest('[data-attach-btn]')) setShowAttach(false);
    };
    document.addEventListener('mousedown', h);
    return ()=>document.removeEventListener('mousedown', h);
  },[]);

  const handleInput = (e)=>{
    const v = e.currentTarget.innerText;
    setText(v);
    if(v.startsWith('/')) setOpenCanned(true); else setOpenCanned(false);
  };

  const pickCanned = (c)=>{
    if(ref.current){ ref.current.innerText = c.p; setText(c.p); }
    setOpenCanned(false);
  };

  const insertHSM = (txt)=>{
    if(ref.current){ ref.current.innerText = txt; setText(txt); }
    setShowHSM(false);
  };

  return (
    <div className="composer-wrap">
      <div className="composer-inner">
        <div className="composer-tabs">
          <button className={`ctab ${!internal?'on':''}`} onClick={()=>onInternal(false)}>
            <i className="fa-regular fa-paper-plane"></i>
            Responder
            <span className="kbd">R</span>
          </button>
          <button className={`ctab ${internal?'on internal':''}`} onClick={()=>onInternal(true)}>
            <i className="fa-solid fa-note-sticky"></i>
            Nota interna
            <span className="kbd">N</span>
          </button>
          <button className={`ctab ${showHSM?'on':''}`} onClick={()=>setShowHSM(s=>!s)}>
            <i className="fa-solid fa-file-lines"></i>
            Plantillas HSM
          </button>
          <div style={{marginLeft:'auto',display:'flex',alignItems:'center',gap:6,padding:'0 8px',fontSize:10.5,color:'var(--text-muted)'}}>
            <i className="fa-solid fa-signal"></i>
            <span>Canal: <b style={{color:'var(--text-soft)',fontWeight:500}}>WhatsApp Business</b></span>
          </div>
        </div>

        {/* HSM picker floating panel */}
        {showHSM && <HSMPickerInline onInsert={insertHSM} onClose={()=>setShowHSM(false)}/>}

        {/* Translate panel */}
        {showTranslate && <TranslatePanel onClose={()=>setShowTranslate(false)}/>}

        {/* Attach menu */}
        {showAttach && <AttachMenu onClose={()=>setShowAttach(false)}/>}

        <div className={`composer ${internal?'internal':''}`} style={{position:'relative'}}>
          {ai && (
            <div className="ai-sugg">
              <div className="aix"><i className="fa-solid fa-wand-magic-sparkles"></i></div>
              <div className="body">
                <div className="lbl">Sugerencia IA · basada en el contexto</div>
                <div className="txt">Hola Julia, confirmado: tu dirección es <b>Calle Mayor 42, 3º B, 28013 Madrid</b>. Reenviamos hoy mismo sin coste y activamos seguimiento prioritario. Te avisaremos cuando salga del almacén.</div>
                <div className="acts">
                  <button className="primary" onClick={()=>{ if(ref.current){ ref.current.innerText = 'Hola Julia, confirmado: tu dirección es Calle Mayor 42, 3º B, 28013 Madrid. Reenviamos hoy mismo sin coste y activamos seguimiento prioritario. Te avisaremos cuando salga del almacén.'; setText(ref.current.innerText);} setAi(false); }}><i className="fa-solid fa-check"></i> Insertar</button>
                  <button><i className="fa-solid fa-rotate"></i> Regenerar</button>
                  <button><i className="fa-solid fa-pen"></i> Más formal</button>
                  <button><i className="fa-solid fa-minimize"></i> Más corto</button>
                  <button onClick={()=>setAi(false)}>Descartar</button>
                </div>
              </div>
            </div>
          )}

          <div className="composer-toolbar">
            <button className="ctb-btn tt" data-tt="Negrita"><b>B</b></button>
            <button className="ctb-btn tt" data-tt="Cursiva"><i style={{fontStyle:'italic'}}>I</i></button>
            <button className="ctb-btn tt" data-tt="Subrayar"><u>U</u></button>
            <button className="ctb-btn tt" data-tt="Tachar"><s>S</s></button>
            <div className="ctb-sep"></div>
            <button className="ctb-btn tt" data-tt="Lista"><i className="fa-solid fa-list-ul"></i></button>
            <button className="ctb-btn tt" data-tt="Lista numerada"><i className="fa-solid fa-list-ol"></i></button>
            <button className="ctb-btn tt" data-tt="Enlace"><i className="fa-solid fa-link"></i></button>
            <button className="ctb-btn tt" data-tt="Código"><i className="fa-solid fa-code"></i></button>
            <div className="ctb-sep"></div>
            <button className="ctb-btn tt" data-tt="Respuestas rápidas ( / )" onClick={()=>setOpenCanned(!openCanned)}><i className="fa-solid fa-bolt"></i></button>
            <button className="ctb-btn tt" data-tt="Mencionar (@)"><i className="fa-solid fa-at"></i></button>
            <button className="ctb-btn tt" data-tt="Emoji"><i className="fa-regular fa-face-smile"></i></button>
            <button className="ctb-btn tt ai" data-tt="Sugerencia IA (⌘J)" onClick={()=>setAi(!ai)}><i className="fa-solid fa-wand-magic-sparkles"></i></button>
            <div className="ctb-spacer"></div>
            <button className={`ctb-lang tt ${showTranslate?'on':''}`} data-tt="Traducir antes de enviar" onClick={()=>setShowTranslate(s=>!s)}>
              <i className="fa-solid fa-language"></i>
              <span>ES → ES</span>
              <i className="fa-solid fa-chevron-down" style={{fontSize:8}}></i>
            </button>
          </div>

          {replyTo && (
            <div className="composer-reply">
              <i className="fa-solid fa-reply" style={{marginTop:2}}></i>
              <div className="body">
                <div className="who">Respondiendo a {replyTo.who}</div>
                <div className="qt">{replyTo.text}</div>
              </div>
              <button className="x" onClick={onClearReply}><i className="fa-solid fa-xmark"></i></button>
            </div>
          )}

          <div ref={ref} className="composer-input" contentEditable suppressContentEditableWarning
               data-ph="Escribe tu respuesta… usa / para respuestas rápidas, @ para mencionar."
               onInput={handleInput}></div>

          {openCanned && (
            <div className="canned-pop">
              <div className="canned-head">
                <i className="fa-solid fa-bolt" style={{fontSize:10}}></i>
                <input defaultValue={text.startsWith('/')?text:'/'}/>
                <span className="kbd">esc</span>
              </div>
              <div className="canned-body">
                {CONV_DATA.CANNED.map(c=>(
                  <div key={c.sh} className="canned-item" onClick={()=>pickCanned(c)}>
                    <div className="t">{c.t}<span className="sh">{c.sh}</span></div>
                    <div className="p">{c.p}</div>
                  </div>
                ))}
              </div>
              <div className="canned-foot">
                <span><span className="kbd">↑↓</span> navegar</span>
                <span><span className="kbd">↵</span> insertar</span>
                <span style={{marginLeft:'auto'}}>Gestionar plantillas →</span>
              </div>
            </div>
          )}

          <div className="composer-foot">
            <div className="cf-left">
              <button className="ctb-btn tt" data-tt="Adjuntar (⌘⇧A)" data-attach-btn="1" onClick={()=>setShowAttach(s=>!s)}>
                <i className="fa-solid fa-paperclip"></i>
              </button>
              <button className="ctb-btn tt" data-tt="Imagen"><i className="fa-regular fa-image"></i></button>
              <button className="ctb-btn tt" data-tt="Nota de voz"><i className="fa-solid fa-microphone"></i></button>
              <button className="ctb-btn tt" data-tt="Programar envío"><i className="fa-regular fa-clock"></i></button>
            </div>
            <div className="cf-spacer"></div>
            <span className="cf-hint">⌘↵ para enviar</span>
            <button className={`send-btn ${!text.trim()?'disabled':''}`}>
              <i className="fa-regular fa-paper-plane"></i>
              <span>{internal?'Añadir nota':'Enviar'}</span>
              <span className="split"></span>
              <i className="fa-solid fa-caret-down"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

/* ═══════════════ RIGHT PANEL ═══════════════ */
const RightPanel = ({ conv }) => {
  const [closed, setClosed] = React.useState({});
  const toggle = (k)=>setClosed(s=>({...s,[k]:!s[k]}));
  const Sec = ({ id, title, children, add }) => (
    <div className={`r-section ${closed[id]?'closed':''}`}>
      <div className="r-sec-head" onClick={()=>toggle(id)}>
        <span>{title}</span>
        {add && <button className="add" onClick={(e)=>{e.stopPropagation();}}><i className={`fa-solid ${add}`}></i></button>}
        <i className="fa-solid fa-chevron-down caret"></i>
      </div>
      <div className="r-sec-body">{children}</div>
    </div>
  );
  const assignee = conv.assignee ? CONV_DATA.AGENTS.find(a=>a.id===conv.assignee) : null;

  return (
    <div className="right" style={{position:'relative'}}>
      <div className="resize-x"></div>
      <div className="r-head">
        <Avatar initials={conv.initials} color={conv.color} ch={conv.channel} size={56} round={14}/>
        <div>
          <div className="nm">{conv.contact}</div>
          <div className="role">{conv.company || 'Cliente particular'}</div>
        </div>
        <div className="quick">
          <button className="tt" data-tt="Llamar"><i className="fa-solid fa-phone"></i></button>
          <button className="tt" data-tt="Email"><i className="fa-regular fa-envelope"></i></button>
          <button className="tt" data-tt="Ver perfil"><i className="fa-solid fa-user"></i></button>
          <button className="tt" data-tt="Más"><i className="fa-solid fa-ellipsis"></i></button>
        </div>
        <div className="tags">
          {conv.tags.map(t=>{
            const tag = CONV_DATA.TAGS.find(x=>x.id===t);
            return (
              <span key={t} className="r-tag">
                <span className="d" style={{background:tag.color}}></span>
                {tag.label}
                <span className="x"><i className="fa-solid fa-xmark"></i></span>
              </span>
            );
          })}
          <span className="r-tag add"><i className="fa-solid fa-plus" style={{fontSize:8}}></i> Añadir</span>
        </div>
      </div>
      <div className="r-scroll">
        <Sec id="conv" title="Conversación">
          <div className="r-select"><span className="l">Estado</span><span className="v"><span style={{width:7,height:7,borderRadius:50,background:'var(--success)',display:'inline-block'}}></span> Abierta</span><i className="fa-solid fa-chevron-down chev"></i></div>
          <div className="r-select"><span className="l">Agente</span><span className="v">{assignee?<><span className={`assign-dot ${assignee.color}`} style={{width:16,height:16,fontSize:8}}>{assignee.initials}</span>{assignee.name}</>:'Sin asignar'}</span><i className="fa-solid fa-chevron-down chev"></i></div>
          <div className="r-select"><span className="l">Equipo</span><span className="v"><i className="fa-solid fa-users" style={{fontSize:10}}></i>Soporte N1</span><i className="fa-solid fa-chevron-down chev"></i></div>
          <div style={{fontSize:10.5,fontWeight:500,color:'var(--text-muted)',padding:'7px 4px 3px'}}>Prioridad</div>
          <div className="r-prio-group">
            {['low','normal','high','urgent'].map(p=>(
              <div key={p} className={`r-prio-btn ${p} ${conv.priority===p?'on':''}`}>
                <span className="d"></span>
                {p==='low'?'Baja':p==='normal'?'Normal':p==='high'?'Alta':'Urgente'}
              </div>
            ))}
          </div>
        </Sec>
        <Sec id="contact" title="Ficha del contacto" add="fa-pen">
          <div className="r-field"><span className="lbl">Nombre</span><span className="val">{conv.contact}</span></div>
          <div className="r-field"><span className="lbl">Empresa</span><span className="val">{conv.company||'—'}</span></div>
          <div className="r-field"><span className="lbl">Email</span><span className="val">{conv.email||'—'}</span></div>
          <div className="r-field"><span className="lbl">Teléfono</span><span className="val">{conv.phone||'—'}</span></div>
          <div className="r-field"><span className="lbl">Idioma</span><span className="val">🇪🇸 Español</span></div>
          <div className="r-field"><span className="lbl">Zona</span><span className="val">Europe/Madrid</span></div>
        </Sec>
        <Sec id="tkts" title="Tickets relacionados" add="fa-plus">
          {[{id:'T-8421',t:'Pedido extraviado — reenvío',s:'urgent',d:'abierto · hace 2h'},{id:'T-7890',t:'Consulta factura mayo',s:'ok',d:'14 mar'},{id:'T-6512',t:'Cambio de talla',s:'ok',d:'02 feb'}].map(tk=>(
            <div key={tk.id} className="r-ticket">
              <span className="tkt-id">#{tk.id}</span>
              <div className="body"><div className="t">{tk.t}</div><div className="s">{tk.d}</div></div>
            </div>
          ))}
        </Sec>
        <Sec id="prev" title="Conversaciones anteriores">
          {[{ch:'wa',d:'Seguimiento pedido #8400',t:'14 mar'},{ch:'fb',d:'Consulta disponibilidad',t:'22 feb'},{ch:'widget',d:'Recuperación contraseña',t:'05 feb'}].map((c,i)=>(
            <div key={i} className="r-conv-prev"><span className={`chdot ${c.ch}`} style={{background:`var(--${c.ch})`}}></span><span className="d">{c.d}</span><span className="time">{c.t}</span></div>
          ))}
        </Sec>
        <Sec id="notes" title="Notas internas" add="fa-plus">
          <div className="r-note">Cliente recurrente, pide siempre envío exprés. Habla por WhatsApp, recibe facturas por email.<div className="meta"><span>Carlos R.</span><span>hace 3 días</span></div></div>
          <button className="r-note-add"><i className="fa-solid fa-plus"></i> Añadir nota…</button>
        </Sec>
        <Sec id="integ" title="Integraciones">
          {[{bg:'#95bf47',i:'fa-brands fa-shopify',n:'Shopify',s:'12 pedidos · 1.284,50 €'},{bg:'#2563eb',i:'fa-solid fa-building',n:'CRM Holded',s:'Lead calificado · Score 87'},{bg:'#f59e0b',i:'fa-solid fa-credit-card',n:'Stripe',s:'3 cobros · 0 disputas'}].map(ig=>(
            <div key={ig.n} className="r-integ"><div className="logo" style={{background:ig.bg}}><i className={ig.i}></i></div><div className="body"><div className="nm">{ig.n}</div><div className="s">{ig.s}</div></div><i className="fa-solid fa-chevron-right" style={{color:'var(--text-muted)',fontSize:10}}></i></div>
          ))}
        </Sec>
      </div>
    </div>
  );
};

Object.assign(window, { Composer, RightPanel });
