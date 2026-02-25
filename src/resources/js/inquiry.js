import '../css/app.css';
import { createApp } from 'vue';
import vuetify from './plugins/vuetify';
import InquiryForm from './Pages/Inquiry/Form.vue';

createApp(InquiryForm).use(vuetify).mount('#inquiry-app');
