function tutorFormData(data = []) {
    const formData = new FormData();
    data.forEach((item) => {
        for (const [key, value] of Object.entries(item)) {
            formData.set(key, value)
        }
    });
    formData.set(window.tutor_get_nonce_data(true).key, window.tutor_get_nonce_data(true).value);
    return formData;
}
export default tutorFormData;