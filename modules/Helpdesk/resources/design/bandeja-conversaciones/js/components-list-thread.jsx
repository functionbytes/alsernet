/* ═══════════════ LIST (conversaciones) ═══════════════ */

/* Canales pills superiores */
const CHAN_META = {
  whatsapp:{cls:'wa', i:'fa-brands fa-whatsapp', lbl:'WhatsApp'},
  instagram:{cls:'ig', i:'fa-brands fa-instagram', lbl:'Instagram'},
  facebook:{cls:'fb', i:'fa-brands fa-facebook-messenger', lbl:'Facebook'},
  widget:{cls:'widget', i:'fa-regular fa-comment', lbl:'Widget'},
  email:{cls:'email', i:'fa-regular fa-envelope', lbl:'Email'},
};

const ConvItem = ({ c, on, selected, onClick, onSelect, onSwipe }) => {
  const assignee = c.assignee ? CONV_DATA.AGENTS.find(a=>a.id===c.assignee) : null;
  const priorityTag = c.priority==='urgent' ? 'urgent' : c.priority==='high'?'high':c.priority==='low'?'low':null;
  const [showActions, setShowActions] = React.useState(false);
  const [pinned, setPinned] = React.useState(c.pinned || c.id===3);

  return (
    <div
      className={`conv ${on?'on':''} ${c.unread?'unread':''} ${selected?'selected':''} ${pinned?'pinned':''}`}
      onClick={onClick}
      onMouseEnter={()=>setShowActions(true)}
      onMouseLeave={()=>setShowActions(false)}
    >
      {c.priority==='urgent' && <span className="conv-urgent-rail"></span>}
      {pinned && <i className="fa-solid fa-thumbtack conv-pin"></i>}
      <input
        type="checkbox"
        checked={!!selected}
        onChange={e=>{ e.stopPropagation(); onSelect&&onSelect(c.id); }}
        onClick={e=>e.stopPropagation()}
      />
      <Avatar initials={c.initials} color={c.color} ch={c.channel} size={32} round={8}/>
      <div className="body">
        <div className="row1">
          <span className="name">{c.contact}</span>
          {c.vip && <i className="fa-solid fa-star vip-star" title="VIP"></i>}
          <span className="time">{c.time}</span>
        </div>
        <div className="row2">
          <span className="preview">
            {c.typing ? (
              <span className="typing-preview">
                <span className="dots"><span></span><span></span><span></span></span>
                escribiendo…
              </span>
            ) : c.out ? (
              <>
                <i className={`fa-solid fa-check-double chk ${c.read===false?'':'read'}`}></i>
                <span className="me">Tú:</span> {c.preview.replace(/^Tú: ?/, '')}
              </>
            ) : c.preview}
          </span>
          <div className="meta">
            {c.sla && <span className={`sla ${c.sla.state}`} title={`SLA: ${c.sla.label} restantes`}>
              {c.sla.state==='breach' && <i className="fa-solid fa-triangle-exclamation" style={{fontSize:8,marginRight:3}}></i>}
              {c.sla.label}
            </span>}
            {priorityTag && <span className={`tag ${priorityTag}`}>{c.priority.toUpperCase().slice(0,3)}</span>}
            {c.unread>0 && <span className="ucount">{c.unread>9?'9+':c.unread}</span>}
            {assignee && <span className={`assign-dot ${assignee.color} tt`} data-tt={assignee.name}>{assignee.initials}</span>}
          </div>
        </div>
        {/* Acciones rápidas on-hover, Front-style */}
        {showActions && !selected && (
          <div className="conv-hover-acts" onClick={e=>e.stopPropagation()}>
            <button className="tt" data-tt="Fijar" onClick={()=>setPinned(!pinned)}>
              <i className={`fa-solid fa-thumbtack ${pinned?'on':''}`}></i>
            </button>
            <button className="tt" data-tt="Silenciar"><i className="fa-solid fa-bell-slash"></i></button>
            <button className="tt" data-tt="Archivar"><i className="fa-solid fa-box-archive"></i></button>
            <button className="tt" data-tt="Snooze"><i className="fa-regular fa-clock"></i></button>
            <button className="tt" data-tt="Marcar leída"><i className="fa-regular fa-envelope-open"></i></button>
          </div>
        )}
      </div>
    </div>
  );
};

const ConvList = ({ currentId, onPick, selected, onSelect, onToggleBulk }) => {
  const [filter, setFilter] = React.useState('all');
  const [query, setQuery] = React.useState('');
  const [chFilter, setChFilter] = React.useState('all');

  const filters = [
    { id:'all', t:'Todas', c:11 },
    { id:'unread', t:'Sin leer', i:'fa-regular fa-envelope', c:5 },
    { id:'mine', t:'Mías', c:3 },
    { id:'ur', t:'Urgentes', c:2 },
    { id:'menc', t:'@Mí', c:1 },
  ];

  // Filter pipeline
  let data = CONV_DATA.CONVERSATIONS.slice();
  if(filter==='unread') data = data.filter(c=>c.unread>0);
  if(filter==='mine') data = data.filter(c=>c.assignee===1);
  if(filter==='ur') data = data.filter(c=>c.priority==='urgent');
  if(chFilter!=='all') data = data.filter(c=>c.channel===chFilter);
  if(query) data = data.filter(c=>
    c.contact.toLowerCase().includes(query.toLowerCase()) ||
    (c.preview||'').toLowerCase().includes(query.toLowerCase())
  );

  // Pinned at top
  const pinned = data.filter(c=>c.id===3);
  const rest = data.filter(c=>c.id!==3);
  const byGroup = {};
  rest.forEach(c=>{ (byGroup[c.group] = byGroup[c.group] || []).push(c); });
  const groupOrder = ['Hoy','Ayer','Esta semana'];

  return (
    <div className="list">
      <div className="list-head">
        <div className="list-search">
          <i className="fa-solid fa-magnifying-glass"></i>
          <input
            placeholder="Buscar conversación, cliente, pedido…"
            value={query}
            onChange={e=>setQuery(e.target.value)}
          />
          {query ? (
            <button className="x-btn" onClick={()=>setQuery('')}><i className="fa-solid fa-xmark"></i></button>
          ) : <span className="kbd">/</span>}
        </div>
        <button className="topbtn tt" data-tt="Filtros avanzados"><i className="fa-solid fa-filter"></i></button>
        <button className="topbtn tt" data-tt="Nueva conversación"><i className="fa-solid fa-pen-to-square"></i></button>
      </div>

      {/* Canales row (pills con contador) */}
      <div className="list-channels">
        <button className={`chpill ${chFilter==='all'?'on':''}`} onClick={()=>setChFilter('all')}>
          <i className="fa-solid fa-layer-group"></i><span>Todos</span><span className="c">248</span>
        </button>
        {Object.entries(CHAN_META).filter(([k])=>k!=='email').map(([k,v])=>{
          const count = CONV_DATA.CONVERSATIONS.filter(c=>c.channel===k).length;
          return (
            <button key={k} className={`chpill ${chFilter===k?'on':''} ${v.cls}`} onClick={()=>setChFilter(k)}>
              <i className={v.i}></i><span>{v.lbl}</span><span className="c">{count}</span>
            </button>
          );
        })}
      </div>

      <div className="list-sub">
        {filters.map(f=>(
          <button key={f.id} className={`chip ${filter===f.id?'on':''}`} onClick={()=>setFilter(f.id)}>
            {f.i && <i className={f.i}></i>}
            <span>{f.t}</span>
            <span className="c">{f.c}</span>
          </button>
        ))}
        <button className="list-sort tt" data-tt="Orden">
          <i className="fa-solid fa-arrow-down-wide-short"></i>
          <span>Recientes</span>
        </button>
      </div>

      {selected && selected.size>0 && (
        <div className="list-bulkbar">
          <span className="sel">{selected.size} seleccionada{selected.size!==1?'s':''}</span>
          <button className="bulkbtn"><i className="fa-solid fa-user-check"></i> Asignar</button>
          <button className="bulkbtn"><i className="fa-solid fa-tag"></i> Etiquetar</button>
          <button className="bulkbtn"><i className="fa-solid fa-box-archive"></i> Archivar</button>
          <button className="bulkbtn"><i className="fa-solid fa-check"></i> Cerrar</button>
          <button className="x" onClick={()=>onToggleBulk('clear')}><i className="fa-solid fa-xmark"></i></button>
        </div>
      )}

      <div className="list-scroll">
        {pinned.length>0 && (
          <>
            <div className="list-group-label pinned-label">
              <i className="fa-solid fa-thumbtack"></i>
              <span>Fijadas</span>
              <span className="c">{pinned.length}</span>
            </div>
            {pinned.map(c=>(
              <ConvItem key={c.id} c={c} on={currentId===c.id} selected={selected&&selected.has(c.id)}
                onClick={()=>onPick(c.id)} onSelect={onSelect}/>
            ))}
          </>
        )}
        {groupOrder.map(g=> byGroup[g] && (
          <React.Fragment key={g}>
            <div className="list-group-label">
              <span>{g}</span>
              <span className="c">{byGroup[g].length}</span>
            </div>
            {byGroup[g].map(c=>(
              <ConvItem key={c.id} c={c} on={currentId===c.id} selected={selected&&selected.has(c.id)}
                onClick={()=>onPick(c.id)} onSelect={onSelect}/>
            ))}
          </React.Fragment>
        ))}
        {data.length===0 && (
          <div className="list-empty">
            <i className="fa-regular fa-inbox"></i>
            <div className="t">Sin resultados</div>
            <div className="s">Ajusta los filtros o la búsqueda</div>
          </div>
        )}
      </div>
    </div>
  );
};

/* ═══════════════ THREAD (header + body + composer) ═══════════════ */
const Message = ({ m, onReply, translate, isNew }) => {
  const [showActs, setShowActs] = React.useState(false);
  const [showReact, setShowReact] = React.useState(false);
  const [copied, setCopied] = React.useState(false);

  const copyText = (e)=>{
    e.stopPropagation();
    navigator.clipboard && navigator.clipboard.writeText(m.body||'');
    setCopied(true);
    setTimeout(()=>setCopied(false), 1400);
  };

  return (
    <div
      className={`msg ${m.dir} ${m.internal?'internal':''} ${isNew?'is-new':''}`}
      data-id={m.id}
      onMouseEnter={()=>setShowActs(true)}
      onMouseLeave={()=>{setShowActs(false); setShowReact(false);}}
    >
      {m.dir==='in' && <Avatar initials={m.ai} color={m.color} size={28} round={8}/>}
      <div className="col">
        <div className="meta">
          <span className="who">{m.author}</span>
          {m.internal && <span className="internal-badge"><i className="fa-solid fa-lock"></i>Interna</span>}
          <span className="t">{m.time}</span>
          {m.dir==='out' && !m.internal && (
            <span className="tt" data-tt={m.read?'Leído':'Entregado'}>
              <i className={`fa-solid fa-check-double chk ${m.read?'read':''}`}></i>
            </span>
          )}
        </div>
        <div className="bubble-wrap" style={{position:'relative'}}>
          <div className="bubble">
            {m.replyTo && (
              <div className="reply-q">
                <span className="who">{m.replyTo.who}</span>
                {m.replyTo.text}
              </div>
            )}
            {m.body && <div>{m.body}</div>}
            {m.attachments && (
              <div className="atts">
                {m.attachments.map((a,i)=>{
                  if(a.type==='image') return (
                    <div key={i} className="att-img" style={{'--c1':a.c1,'--c2':a.c2}}>
                      <div className="meta"><span>{a.name}</span><span>{a.size}</span></div>
                    </div>
                  );
                  if(a.type==='audio') return (
                    <div key={i} className="att-audio">
                      <button className="play"><i className="fa-solid fa-play"></i></button>
                      <div className="wave">{Array.from({length:32}).map((_,j)=>(
                        <span key={j} className={j<10?'p':''} style={{height:`${8+Math.abs(Math.sin(j*1.3))*14}px`}}></span>
                      ))}</div>
                      <span className="dur">{a.duration}</span>
                    </div>
                  );
                  if(a.type==='file') return (
                    <div key={i} className="att-file">
                      <div className="fi"><i className="fa-regular fa-file-pdf"></i></div>
                      <div style={{flex:1,minWidth:0}}>
                        <div className="fname">{a.name}</div>
                        <div className="fsz">{a.size}</div>
                      </div>
                      <button className="fi"><i className="fa-solid fa-download"></i></button>
                    </div>
                  );
                  return null;
                })}
              </div>
            )}
            {translate && m.translation && (
              <div className="translation">
                <span className="flag">Traducción · {m.translation.from.toUpperCase()} → EN</span>
                {m.translation.txt}
              </div>
            )}
          </div>

          {/* Acciones flotantes */}
          {showActs && (
            <div className="hover-acts">
              <button className="tt" data-tt="Responder" onClick={()=>onReply&&onReply(m)}><i className="fa-solid fa-reply"></i></button>
              <button className="tt" data-tt="Reaccionar" onClick={e=>{e.stopPropagation();setShowReact(s=>!s);}}><i className="fa-regular fa-face-smile"></i></button>
              <button className="tt" data-tt={copied?'¡Copiado!':'Copiar'} onClick={copyText}>
                <i className={`fa-solid ${copied?'fa-check':'fa-copy'}`} style={copied?{color:'var(--success)'}:{}}></i>
              </button>
              <button className="tt" data-tt="Reenviar"><i className="fa-solid fa-share"></i></button>
              <button className="tt" data-tt="Convertir a ticket"><i className="fa-solid fa-ticket"></i></button>
              <button className="tt" data-tt="Más"><i className="fa-solid fa-ellipsis"></i></button>
            </div>
          )}

          {/* Quick-react picker */}
          {showReact && (
            <div className="react-picker" onClick={e=>e.stopPropagation()}>
              {['👍','❤️','😂','🎉','🙏','🔥'].map(e=>(
                <button key={e} className="rp" onClick={()=>{setShowReact(false);}}>{e}</button>
              ))}
              <button className="rp more"><i className="fa-solid fa-plus"></i></button>
            </div>
          )}
        </div>
        {m.reactions && (
          <div className="reacts">
            {m.reactions.map((r,i)=>(
              <button key={i} className={`react ${r.mine?'mine':''}`}><span>{r.e}</span><span className="c">{r.c}</span></button>
            ))}
            <button className="react add-react" onClick={()=>setShowReact(true)}><i className="fa-regular fa-face-smile"></i><i className="fa-solid fa-plus" style={{fontSize:7}}></i></button>
          </div>
        )}
      </div>
    </div>
  );
};

const ThreadHead = ({ conv, onAction, onToggleRight, rightOn }) => {
  const assignee = conv.assignee ? CONV_DATA.AGENTS.find(a=>a.id===conv.assignee) : null;
  return (
    <>
      <div className="th-head">
        <div className="who">
          <Avatar initials={conv.initials} color={conv.color} ch={conv.channel} size={34} round={9}/>
          <div>
            <div className="nm">{conv.contact}</div>
            <div className="sub">
              <span className="online"></span>
              <span>Activa ahora</span>
              <span className="sep">•</span>
              <span>{conv.ch_label}{conv.phone?` · ${conv.phone}`:''}</span>
            </div>
          </div>
        </div>
        <div className="actions">
          <button className="th-action tt" data-tt="Llamar"><i className="fa-solid fa-phone"></i></button>
          <button className="th-action tt" data-tt="Traducir" onClick={()=>onAction('translate')}><i className="fa-solid fa-language"></i></button>
          <button className="th-action tt" data-tt="Buscar en el hilo"><i className="fa-solid fa-magnifying-glass"></i></button>
          <button className="th-action tt" data-tt="Snooze"><i className="fa-regular fa-clock"></i></button>
          <button className="th-action tt" data-tt="Asignar (A)" onClick={()=>onAction('assign')}><i className="fa-solid fa-user-check"></i></button>
          <button className="th-action tt" data-tt="Etiquetar (T)" onClick={()=>onAction('tags')}><i className="fa-solid fa-tag"></i></button>
          <button className="th-action tt" data-tt="Fusionar" onClick={()=>onAction('merge')}><i className="fa-solid fa-code-merge"></i></button>
          <button className="th-action tt" data-tt={rightOn?'Ocultar panel':'Mostrar panel'} onClick={onToggleRight}>
            <i className={`fa-solid ${rightOn?'fa-sidebar-flip':'fa-sidebar'}`}></i>
            <i className="fa-regular fa-square"></i>
          </button>
          <button className="th-action tt" data-tt="Más"><i className="fa-solid fa-ellipsis"></i></button>
          <button className="th-action primary" onClick={()=>onAction('close')}><i className="fa-solid fa-check"></i>Cerrar</button>
        </div>
      </div>
      <div className="th-sub">
        <span className={`pill status-${conv.status}`}>
          <i className="fa-solid fa-circle"></i>
          {conv.status==='open'?'Abierta':conv.status==='pending'?'En espera':'Cerrada'}
        </span>
        <span className="pill"><i className="fa-solid fa-flag"></i>{conv.priority==='urgent'?'Urgente':conv.priority==='high'?'Alta':conv.priority==='low'?'Baja':'Normal'}</span>
        {assignee && <span className="pill"><span className={`assign-dot ${assignee.color}`} style={{width:14,height:14,fontSize:7}}>{assignee.initials}</span>{assignee.name}</span>}
        <span className="pill"><i className="fa-solid fa-users"></i>Soporte</span>
        {conv.tags.map(t=>{
          const tag = CONV_DATA.TAGS.find(x=>x.id===t);
          return <span key={t} className="pill"><span style={{width:7,height:7,borderRadius:2,background:tag.color}}></span>{tag.label}</span>;
        })}
        {conv.sla && (
          <div className="sla-bar">
            <i className="fa-regular fa-clock"></i>
            <span>SLA</span>
            <div className="bar"><div className={`fill ${conv.sla.state}`} style={{width:conv.sla.state==='breach'?'100%':conv.sla.state==='warn'?'72%':'38%'}}></div></div>
            <span>{conv.sla.label} restantes</span>
          </div>
        )}
      </div>
    </>
  );
};

/* Scroll-to-bottom FAB */
const ScrollFab = ({ onClick, unread }) => (
  <button className="scroll-fab" onClick={onClick}>
    {unread>0 && <span className="bad">{unread}</span>}
    <i className="fa-solid fa-arrow-down"></i>
  </button>
);

Object.assign(window, { ConvItem, ConvList, Message, ThreadHead, ScrollFab });
