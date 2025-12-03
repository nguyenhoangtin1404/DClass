(() => {
  const MONTHS = ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];
  const WEEKDAYS = ['T2','T3','T4','T5','T6','T7','CN'];
  const format = (d)=>{
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    const day = String(d.getDate()).padStart(2,'0');
    return `${day}/${m}/${y}`;
  };
  const parse = (val)=>{
    if(!val) return null;
    const parts = val.split(/[-/]/);
    if(parts.length===3){
      let y,m,d;
      if(parts[0].length===4){ y=+parts[0]; m=+parts[1]; d=+parts[2]; }
      else { d=+parts[0]; m=+parts[1]; y=+parts[2]; if(y<100) y+=2000; }
      const dt = new Date(y, m-1, d);
      return isNaN(dt.getTime()) ? null : dt;
    }
    const t = Date.parse(val);
    return isNaN(t) ? null : new Date(t);
  };
  const adjustMonday = (n)=> (n===0 ? 6 : n-1); // 0=Mon, 6=Sun

  class DatePicker {
    constructor(input){
      this.input = input;
      this.value = parse(input.value) || new Date();
      this.current = new Date(this.value);
      this.pop = null;
      this.build();
      input.addEventListener('focus', ()=>this.open());
      input.addEventListener('click', ()=>this.open());
    }
    build(){
      const pop = document.createElement('div');
      pop.className='dp-pop';
      pop.innerHTML = `
        <div class="dp-head">
          <button class="dp-btn dp-prev" aria-label="Trước">&lt;</button>
          <div class="dp-title"></div>
          <button class="dp-btn dp-next" aria-label="Sau">&gt;</button>
        </div>
        <div class="dp-grid dp-week"></div>
        <div class="dp-grid dp-days"></div>
      `;
      document.body.appendChild(pop);
      pop.style.display = 'none';
      this.pop = pop;
      pop.querySelector('.dp-prev').onclick = ()=>{ this.current.setMonth(this.current.getMonth()-1); this.render(); };
      pop.querySelector('.dp-next').onclick = ()=>{ this.current.setMonth(this.current.getMonth()+1); this.render(); };
      const week = pop.querySelector('.dp-week');
      WEEKDAYS.forEach(d=>{ const c=document.createElement('div'); c.className='dp-cell dp-weekday'; c.textContent=d; week.appendChild(c); });
      document.addEventListener('click',(e)=>{
        if(this.pop && !this.pop.contains(e.target) && e.target!==this.input){ this.close(); }
      });
      this.render();
    }
    render(){
      if(!this.pop) return;
      const title = this.pop.querySelector('.dp-title');
      title.textContent = `${this.current.getFullYear()} — ${MONTHS[this.current.getMonth()]}`;
      const daysWrap = this.pop.querySelector('.dp-days');
      daysWrap.innerHTML='';
      const first = new Date(this.current.getFullYear(), this.current.getMonth(), 1);
      const offset = adjustMonday(first.getDay());
      for(let i=0;i<offset;i++){ const c=document.createElement('div'); c.className='dp-cell disabled'; daysWrap.appendChild(c); }
      const lastDay = new Date(this.current.getFullYear(), this.current.getMonth()+1, 0).getDate();
      for(let d=1; d<=lastDay; d++){
        const cell=document.createElement('div');
        cell.className='dp-cell';
        cell.textContent=d;
        const dt = new Date(this.current.getFullYear(), this.current.getMonth(), d);
        if (this.value && format(dt)===format(this.value)) cell.classList.add('active');
        cell.onclick=()=>{
          this.value = dt;
          this.input.value = format(dt);
          this.close();
        };
        daysWrap.appendChild(cell);
      }
    }
    open(){
      if(!this.pop) return;
      this.pop.style.display='block';
      const rect = this.input.getBoundingClientRect();
      const top = window.scrollY + rect.bottom + 6;
      let left = window.scrollX + rect.left;
      // giữ popup trong viewport
      const popRect = this.pop.getBoundingClientRect();
      const maxLeft = window.scrollX + document.documentElement.clientWidth - popRect.width - 8;
      if (left > maxLeft) left = maxLeft;
      if (left < 8) left = 8;
      this.pop.style.top = `${top}px`;
      this.pop.style.left = `${left}px`;
      this.render();
    }
    close(){
      if(this.pop) this.pop.style.display='none';
    }
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    const inputs = document.querySelectorAll('input[data-datepicker]');
    inputs.forEach(inp=>{
      if(!inp.dataset.__dp){ inp.dataset.__dp='1'; new DatePicker(inp); }
    });
  });
})();
