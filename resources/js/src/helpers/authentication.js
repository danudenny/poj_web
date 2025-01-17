import axios from 'axios';
import { useToast } from "vue-toastification";

const apiClient = axios.create({
    baseURL: '/api/v1/auth',
    headers: {
        'Content-Type': 'application/json'
    }
});

export default {
    /**
     * acoder.dev30@gmail.com
     * tooHard
     * @param credentials
     * @returns {Promise<*>}
     */
    async login(credentials) {
        try {
            const response = await apiClient.post('/login', credentials);
            localStorage.setItem('AVAILABLE_USER_ROLES', JSON.stringify(response.data.data.user.availableRole));
            useToast().success("Login successfully");
            return response.data.data;
        } catch (e) {
            useToast().error(e.response.data.message );
            throw new Error(e.response.data.message);
        }
    },

    async logout(){
        try {
            const response = await apiClient.post('/logout');
            localStorage.removeItem('AVAILABLE_USER_ROLES');
            useToast().success("Logout successfully");
            return response.data.data;
        } catch (e) {
            throw new Error(e.response.data.message)
        }
    },

    async forgetPassword(email) {
        try {
            const response = await apiClient.post('/forget_password', { email });
            return response.data.data;
        } catch (e) {
            throw new Error(e.response.data.message);
        }
    }
};
