import { createApp } from 'vue'
import './adminator/styles/index.scss'
import './style.css'
import App from './App.vue'
import router from './router'

createApp(App).use(router).mount('#app')
