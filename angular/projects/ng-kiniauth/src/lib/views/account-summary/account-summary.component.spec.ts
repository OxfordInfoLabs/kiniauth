import { AccountSummaryComponent } from './account-summary.component';
import { BehaviorSubject } from 'rxjs';

class MockAuthService  {

    authUser: BehaviorSubject<any> = new BehaviorSubject(null);
    loggedInUser = null;

    getLoggedInUser() {
        return Promise.resolve(this.loggedInUser);
    }

}

describe('AccountSummaryComponent', () => {
    let component: AccountSummaryComponent;
    let service: any;

    beforeEach(() => {
        service = new MockAuthService();
        component = new AccountSummaryComponent(service);
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
            expect(component.security).toBe(null);
        });
    });

    it ('user object should be populated with logged in user, if we are logged in', () => {
        service.authUser.next({name: 'Mr Test', email: 'test@me.com'});
        component.ngOnInit().then(() => {
            expect(component.security).toBeTruthy();
            expect(component.security.name).toBe('Mr Test');
        });
    });
});
