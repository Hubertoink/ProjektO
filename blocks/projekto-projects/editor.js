(function(wp){
	const {registerBlockType} = wp.blocks;
	const {createElement: el, Fragment} = wp.element;
	const {InspectorControls} = wp.blockEditor;
	const {PanelBody, ToggleControl, RangeControl, SelectControl, CheckboxControl, Spinner} = wp.components;
	const {useSelect} = wp.data;
	const ServerSideRender = wp.serverSideRender;

	function DefaultStatusSelect({value, onChange}){
		const terms = useSelect(sel => sel('core').getEntityRecords('taxonomy', 'projekto_status', {per_page:100, orderby:'name', order:'asc'}), []);
		if(terms === undefined) return el(Spinner);
		const options = [{label:'Alle', value:'0'}].concat((terms||[]).map(t => ({label:t.name, value:String(t.id)})));
		return el(SelectControl,{
			label:'Standard-Status (vorausgewählt)',
			value:String(value||0),
			options,
			onChange:v=>onChange(parseInt(v,10)||0)
		});
	}

	function TermChecklist({taxonomy, label, selected, onChange}){
		const terms = useSelect(sel => sel('core').getEntityRecords('taxonomy', taxonomy, {per_page:100, orderby:'name', order:'asc'}), [taxonomy]);
		if(terms === null) return el('p',null,'Keine Begriffe.');
		if(terms === undefined) return el(Spinner);
		return el('div',null,
			el('p',{style:{fontWeight:600,margin:'6px 0'}},label),
			terms.map(t => el(CheckboxControl,{
				key:t.id,
				label:t.name,
				checked:(selected||[]).includes(t.id),
				onChange:v=>{
					let next = (selected||[]).slice();
					if(v){ if(!next.includes(t.id)) next.push(t.id); }
					else { next = next.filter(x=>x!==t.id); }
					onChange(next);
				}
			}))
		);
	}

	registerBlockType('projekto/projects',{
		edit({attributes:a, setAttributes:set}){
			return el(Fragment,null,
				el(InspectorControls,null,
					el(PanelBody,{title:'Filter',initialOpen:true},
						el(TermChecklist,{taxonomy:'projekto_status',label:'Status',selected:a.statuses,onChange:v=>set({statuses:v})}),
						el('div',{style:{height:8}}),
						el(TermChecklist,{taxonomy:'projekto_zustaendigkeit',label:'Zuständigkeit',selected:a.responsibles,onChange:v=>set({responsibles:v})}),
						el('div',{style:{height:8}}),
						el(TermChecklist,{taxonomy:'projekto_arbeitsbereich',label:'Arbeitsbereich',selected:a.arbeitsbereiche,onChange:v=>set({arbeitsbereiche:v})})
					),
					el(PanelBody,{title:'Darstellung',initialOpen:true},
						el(ToggleControl,{label:'Legende anzeigen',checked:!!a.showLegend,onChange:v=>set({showLegend:!!v})}),
						el(ToggleControl,{label:'Details im Modal öffnen',checked:!!a.showDetails,onChange:v=>set({showDetails:!!v})}),
							el(ToggleControl,{label:'Status-Punkt (Pin) im Collapsed Mode',checked:!!a.showStatusBadgeCollapsed,onChange:v=>set({showStatusBadgeCollapsed:!!v})}),
						el(ToggleControl,{label:'Auf Projektseite verlinken',checked:!!a.linkToSingle,onChange:v=>set({linkToSingle:!!v})}),
						el(ToggleControl,{label:'Kurzbeschreibung anzeigen',checked:!!a.showExcerpt,onChange:v=>set({showExcerpt:!!v})}),
						el(ToggleControl,{label:'Zuständigkeit mit Foto',checked:!!a.showResponsiblePhoto,onChange:v=>set({showResponsiblePhoto:!!v})}),
						el(ToggleControl,{label:'Eckige Badges',checked:!!a.squareBadges,onChange:v=>set({squareBadges:!!v})}),
						el(DefaultStatusSelect,{value:a.defaultStatus,onChange:v=>set({defaultStatus:v})}),
						el(RangeControl,{label:'Max. Anzahl (0=alle)',value:a.limit||0,min:0,max:50,onChange:v=>set({limit:v||0})}),
						el(SelectControl,{label:'Sortierung',value:a.orderBy,options:[{label:'Titel',value:'title'},{label:'Datum',value:'date'}],onChange:v=>set({orderBy:v})}),
						el(SelectControl,{label:'Reihenfolge',value:a.order,options:[{label:'A→Z / Älteste',value:'ASC'},{label:'Z→A / Neueste',value:'DESC'}],onChange:v=>set({order:v})})
					)
				),
				el(ServerSideRender,{block:'projekto/projects',attributes:a})
			);
		},
		save(){return null;}
	});
})(window.wp);
