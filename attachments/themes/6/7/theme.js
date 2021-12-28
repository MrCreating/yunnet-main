class MainRender {
    constructor() {
        this.cvs;
        this.ctx;
        this.dpi;
         // create canvas, init context and application css-styles
        this.initCanvas();
        // resize canvas
        this.resize();
    }
    init() {
        addEventListener('resize' , () => {
            this.resize();
        });
       
        this.startRender()
       


    }
    initCanvas() {
        let cvs = document.createElement('canvas');
        cvs.style.transform = 'translate(-50%, -50%)';
        cvs.style['margin-right'] = '-50%';
        cvs.style.position ='fixed';
        cvs.style.display = 'block';
        cvs.style.height = '100%';
        cvs.style.width = '100%';
        cvs.style.left = '0%';
        cvs.style.top = '0%';
        cvs.style["z-index"] = '-1';
        cvs.id = 'yunitEvolution';
        
        this.cvs = document.body.appendChild(cvs);
        this.ctx = this.cvs.getContext('2d');
 
    }
    resize() {
        this.dpi = window.devicePixelRatio;
        this.cvs.width = +getComputedStyle(this.cvs).getPropertyValue("width").slice(0, -2) * this.dpi;
        this.cvs.height = +getComputedStyle(this.cvs).getPropertyValue("height").slice(0, -2) *this.dpi;
    }
    clearCVS() {
        this.ctx.clearRect(0, 0, this.cvs.width,this.cvs.height);
    }
    
    clearCVS() {
        this.ctx.clearRect(0, 0, this.cvs.width,this.cvs.height);
        this.ctx.fillStyle = "black";
        this.ctx.fillRect(0, 0, this.cvs.width,this.cvs.height);
      
    }
    
    startRender() {
        this.clearCVS();
        console.log('test')
        requestAnimationFrame(() => this.startRender());
        
    
    }

 }






new MainRender().init();


        
   