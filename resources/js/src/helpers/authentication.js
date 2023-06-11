import axios from 'axios';

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
            return response.data.data;
        } catch (e) {
            throw new Error(e.response.data.message);
        }
    },

    async logout(){
        try {
            const response = await apiClient.post('/logout');
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
