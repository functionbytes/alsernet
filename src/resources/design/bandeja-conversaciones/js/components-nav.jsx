/* Helpers */
const ChIcon = ({ ch, sm }) => {
  const m = {
    whatsapp: { cls:'wa', i:'fa-brands fa-whatsapp' },
    facebook: { cls:'fb', i:'fa-brands fa-facebook-messenger' },
    instagram:{ cls:'ig', i:'fa-brands fa-instagram' },
    widget:   { cls:'widget', i:'fa-regular fa-comment' },
    email:    { cls:'email', i:'fa-regular fa-envelope' },
  };
  const o = m[ch] || m.widget;
  return <span className={`badge-ch ${o.cls}`}><i className={o.i}></i></span>;
};

const Avatar = ({ initials, color, ch, size=32, round=8 }) => (
  <div className={`av ${color||''}`} style={{width:size,height:size,fontSize:size*0.32,borderRadius:round}}>
    {initials}
    {ch && <ChIcon ch={ch}/>}
  </div>
);

/* ═══════════════ TOPBAR ═══════════════ */
const Topbar = ({ onTheme, theme, onTweaks, onCmdK, onCheat, rightOn, onRight }) => (
  <div className="topbar">
    <div className="brand">
      <div className="brand-mark">F</div>
      <span>Functionbytes</span>
    </div>
    <div className="crumb">
      <span className="dot"></span>
      <span className="crumb-item">Helpdesk</span>
      <i className="fa-solid fa-chevron-right sep"></i>
      <span className="current">Conversaciones</span>
      <i className="fa-solid fa-chevron-right sep"></i>
      <span className="crumb-item">Bandeja de equipo</span>
    </div>
    <div className="search-global" onClick={onCmdK}>
      <i className="fa-solid fa-magnifying-glass"></i>
      <span>Buscar o ejecutar…</span>
      <span className="kbd">⌘K</span>
    </div>
    <button className="topbtn tt" data-tt="Nueva conversación"><i className="fa-solid fa-plus"></i></button>
    <button className="topbtn tt" data-tt="Notificaciones">
      <i className="fa-regular fa-bell"></i>
      <span className="bad">4</span>
    </button>
    <button className="topbtn tt" data-tt="Atajos (?)" onClick={onCheat}><i className="fa-regular fa-keyboard"></i></button>
    <button className="topbtn tt" data-tt={rightOn?'Ocultar panel':'Mostrar panel'} onClick={onRight}>
      <i className="fa-solid fa-table-columns"></i>
    </button>
    <button className="topbtn tt" data-tt={theme==='dark'?'Modo claro':'Modo oscuro'} onClick={onTheme}>
      <i className={theme==='dark'?'fa-solid fa-sun':'fa-solid fa-moon'}></i>
    </button>
    <button className="topbtn tt" data-tt="Tweaks" onClick={onTweaks}><i className="fa-solid fa-sliders"></i></button>
    <button className="avbtn">
      <div className="av">ML</div>
      <span className="nm">María L.</span>
      <i className="fa-solid fa-chevron-down"></i>
    </button>
  </div>
);

/* ═══════════════ RAIL ═══════════════ */
const Rail = ({ view, onView }) => {
  const items = [
    { id:'inbox', i:'fa-solid fa-inbox', tt:'Bandeja', bd:true },
    { id:'mentions', i:'fa-solid fa-at', tt:'Menciones' },
    { id:'assigned', i:'fa-solid fa-user-check', tt:'Asignadas a mí' },
    { id:'pending', i:'fa-solid fa-clock', tt:'En espera' },
    { id:'closed', i:'fa-solid fa-check', tt:'Cerradas' },
  ];
  const tools = [
    { id:'contacts', i:'fa-solid fa-address-book', tt:'Contactos' },
    { id:'camp', i:'fa-solid fa-megaphone', tt:'Campañas' },
    { id:'bot', i:'fa-solid fa-robot', tt:'Agente IA' },
    { id:'tickets', i:'fa-solid fa-ticket', tt:'Tickets' },
    { id:'reports', i:'fa-solid fa-chart-line', tt:'Informes' },
    { id:'settings', i:'fa-solid fa-gear', tt:'Ajustes' },
  ];
  return (
    <div className="rail">
      {items.map(it=>(
        <button key={it.id} className={`rail-btn tt ${view===it.id?'on':''}`} data-tt={it.tt} onClick={()=>onView(it.id)}>
          <i className={it.i}></i>
          {it.bd && <span className="bd"></span>}
        </button>
      ))}
      <div className="rail-sep"></div>
      {tools.map(it=>(
        <button key={it.id} className="rail-btn tt" data-tt={it.tt}>
          <i className={it.i}></i>
        </button>
      ))}
    </div>
  );
};

/* ═══════════════ NAV ═══════════════ */
const Nav = ({ current, onSelect }) => {
  const views = [
    { id:'all', i:'fa-solid fa-layer-group', t:'Todas', c:248 },
    { id:'mine', i:'fa-solid fa-user', t:'Asignadas a mí', c:14 },
    { id:'unassigned', i:'fa-solid fa-user-slash', t:'Sin asignar', c:7 },
    { id:'mentions', i:'fa-solid fa-at', t:'Menciones', c:3, unread:true },
    { id:'following', i:'fa-solid fa-eye', t:'Siguiendo', c:2 },
  ];
  const states = [
    { id:'open', i:'fa-regular fa-circle', t:'Abiertas', c:42 },
    { id:'pending', i:'fa-regular fa-clock', t:'En espera', c:11 },
    { id:'closed', i:'fa-regular fa-circle-check', t:'Cerradas', c:190 },
    { id:'spam', i:'fa-solid fa-ban', t:'Spam', c:0 },
  ];
  const prio = [
    { id:'urgent', d:'#dc2626', t:'Urgente', c:3 },
    { id:'high', d:'#d97706', t:'Alta', c:6 },
    { id:'normal', d:'#2563eb', t:'Normal', c:28 },
    { id:'low', d:'#16a34a', t:'Baja', c:11 },
  ];
  const chans = [
    { id:'whatsapp', cls:'wa', t:'WhatsApp', c:89 },
    { id:'instagram', cls:'ig', t:'Instagram', c:34 },
    { id:'facebook', cls:'fb', t:'Facebook', c:51 },
    { id:'widget', cls:'widget', t:'Widget web', c:74 },
  ];
  const tags = CONV_DATA.TAGS.slice(0,5);
  const groups = [{id:'g1',t:'Ventas',c:22},{id:'g2',t:'Soporte',c:68},{id:'g3',t:'Facturación',c:9}];

  return (
    <div className="nav">
      <div className="nav-head">
        <div>
          <div className="title">Conversaciones</div>
          <div className="title-sub">Bandeja de equipo</div>
        </div>
        <button className="topbtn tt" data-tt="Nueva conversación" style={{marginLeft:'auto'}}><i className="fa-solid fa-plus"></i></button>
      </div>
      <div className="nav-scroll">
        <div className="nav-sec">Vistas <button className="add"><i className="fa-solid fa-plus"></i></button></div>
        {views.map(v=>(
          <div key={v.id} className={`nav-item ${current===v.id?'on':''} ${v.unread?'has-unread':''}`} onClick={()=>onSelect(v.id)}>
            <i className={`ico ${v.i}`}></i>
            <span>{v.t}</span>
            <span className="c">{v.c}</span>
          </div>
        ))}

        <div className="nav-sec">Estado</div>
        {states.map(v=>(
          <div key={v.id} className={`nav-item ${current===v.id?'on':''}`} onClick={()=>onSelect(v.id)}>
            <i className={`ico ${v.i}`}></i>
            <span>{v.t}</span>
            <span className="c">{v.c}</span>
          </div>
        ))}

        <div className="nav-sec">Prioridad</div>
        {prio.map(v=>(
          <div key={v.id} className="nav-item">
            <span className="tag-dot" style={{background:v.d}}></span>
            <span>{v.t}</span>
            <span className="c">{v.c}</span>
          </div>
        ))}

        <div className="nav-sec">Canales <button className="add"><i className="fa-solid fa-plus"></i></button></div>
        {chans.map(v=>(
          <div key={v.id} className="nav-item">
            <span className={`ch-dot ${v.cls}`}></span>
            <span>{v.t}</span>
            <span className="c">{v.c}</span>
          </div>
        ))}

        <div className="nav-sec">Grupos</div>
        {groups.map(v=>(
          <div key={v.id} className="nav-item">
            <i className="ico fa-solid fa-users"></i>
            <span>{v.t}</span>
            <span className="c">{v.c}</span>
          </div>
        ))}

        <div className="nav-sec">Etiquetas <button className="add"><i className="fa-solid fa-plus"></i></button></div>
        {tags.map(v=>(
          <div key={v.id} className="nav-item">
            <span className="tag-dot" style={{background:v.color}}></span>
            <span>{v.label}</span>
          </div>
        ))}
      </div>
    </div>
  );
};

Object.assign(window, { ChIcon, Avatar, Topbar, Rail, Nav });
