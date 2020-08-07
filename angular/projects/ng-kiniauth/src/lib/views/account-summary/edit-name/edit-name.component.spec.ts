import { EditNameComponent } from './edit-name.component';

class MockAuthService  {

    loggedInUser = null;

    getLoggedInUser() {
        return Promise.resolve(this.loggedInUser);
    }

}

describe('EditNameComponent', () => {
    let component: EditNameComponent;
    let service: any;

    beforeEach(() => {
        service = new MockAuthService();
        component = new EditNameComponent(service);
    });

    afterAll(() => {
        service = null;
        component = null;
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it ('user object should be null if not logged in', () => {
        service.loggedInUser = null;
        component.ngOnInit().then(() => {
            expect(component.user).toBe(null);
        });
    });

    it ('user object should be populated with logged in user, if we are logged in', () => {
        service.loggedInUser = {name: 'Mr Test', email: 'test@me.com'};
        component.ngOnInit().then(() => {
            expect(component.user).toBeTruthy();
            expect(component.user.name).toBe('Mr Test');
        });
    });
});
