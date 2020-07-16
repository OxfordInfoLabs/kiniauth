import { LoginComponent } from './login.component';

class MockAuthService {

    public login(email, password) {
        return Promise.resolve(email === password ? 'REQUIRES_2FA' : true);
    }

    public authenticateTwoFactor(twoFaCode) {
        return new Promise((resolve, reject) => {
            if (twoFaCode === '1234') {
                resolve('success');
            } else {
                reject('failed');
            }
        });

    }

}

class MockRouter {

    public navigate(route) {
        return route;
    }

}

describe('LoginComponent', () => {
    let component: LoginComponent;
    let service: any;
    let router: any;

    beforeEach(async () => {
        service = new MockAuthService();
        router = new MockRouter();
        component = new LoginComponent(router, service);
    });

    afterEach(async () => {
        service = null;
        component = null;
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it('should not attempt a login if no email & password is supplied', (done) => {
        let loginSpy = spyOn(service, 'login');
        component.ngOnInit().then(() => {
            component.login();
            expect(loginSpy).not.toHaveBeenCalled();
            done();
        });
    });

    it('should attempt a login if an email and password is supplied', (done) => {
        let loginSpy = spyOn(service, 'login').and.callThrough();
        let routerSpy = spyOn(router, 'navigate');
        component.ngOnInit().then(() => {
            component.email = 'hello';
            component.password = 'password';
            component.login().then(() => {
                expect(loginSpy).toHaveBeenCalledWith('hello', 'password');
                expect(component.loading).toBeFalsy();
                expect(routerSpy).toHaveBeenCalledWith(['/']);
                done();
            });
        });
    });

    it('should attempt a login if an email and password is supplied and navigate to the passed in route', (done) => {
        let loginSpy = spyOn(service, 'login').and.callThrough();
        let routerSpy = spyOn(router, 'navigate');
        component.ngOnInit().then(() => {
            component.email = 'hello';
            component.password = 'password';
            component.loginRoute = '/my-account';
            component.login().then(() => {
                expect(loginSpy).toHaveBeenCalledWith('hello', 'password');
                expect(component.loading).toBeFalsy();
                expect(component.twoFA).toBeFalsy();
                expect(routerSpy).toHaveBeenCalledWith(['/my-account']);
                done();
            });
        });
    });

    it('should prompt for 2FA workflow if login supports it, and navigate to route if successful', (done) => {
        let loginSpy = spyOn(service, 'login').and.callThrough();
        let routerSpy = spyOn(router, 'navigate').and.callThrough();
        component.ngOnInit().then(() => {
            component.email = 'hello';
            component.password = 'hello';
            component.login().then(() => {
                expect(loginSpy).toHaveBeenCalledWith('hello', 'hello');
                expect(component.loading).toBeFalsy();
                expect(routerSpy).not.toHaveBeenCalled();
                expect(component.twoFA).toBeTruthy();
                component.twoFACode = '1234';
                let twoFaSpy = spyOn(service, 'authenticateTwoFactor').and.callThrough();
                component.authenticate()
                    .then(result => {
                        expect(twoFaSpy).toHaveBeenCalledWith('1234');
                        expect(result).toBe('success');
                        expect(routerSpy).toHaveBeenCalledWith(['/']);
                        done();
                    });
            });
        });
    });

    it('should prompt for 2FA workflow if login supports it, and not navigate if incorrect', (done) => {
        let loginSpy = spyOn(service, 'login').and.callThrough();
        let routerSpy = spyOn(router, 'navigate');
        component.ngOnInit().then(() => {
            component.email = 'hello';
            component.password = 'hello';
            component.login().then(() => {
                expect(loginSpy).toHaveBeenCalledWith('hello', 'hello');
                expect(component.loading).toBeFalsy();
                expect(routerSpy).not.toHaveBeenCalled();
                expect(component.twoFA).toBeTruthy();
                component.twoFACode = '123456';
                let twoFaSpy = spyOn(service, 'authenticateTwoFactor').and.callThrough();
                component.authenticate()
                    .then(result => {
                        expect(twoFaSpy).toHaveBeenCalledWith('123456');
                        expect(result).toBe('failed');
                        expect(routerSpy).not.toHaveBeenCalled();
                        expect(component.twoFAError).toBeTruthy();
                        done();
                    });
            });
        });
    });

});
