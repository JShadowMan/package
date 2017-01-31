#include <iostream>
#include <string>
#include <memory>

using namespace std;

class Apple {
    public:
        Apple() : name(new string) { cout << "default constructor" << endl; }
        Apple(const Apple &copy_instance) : name(new string(*copy_instance.name)) { cout << "copy constructor" << endl; }
        Apple &operator=(const Apple &op) { name = make_shared<string>(*op.name); cout << "operator=" << endl; return *this; }
    private:
        shared_ptr<string> name;
};

void func(Apple app);
void r_func(Apple &app);
void p_func(Apple *app);

int main(void) {
    Apple _1;
    Apple _2(_1);
    Apple _3 = _2;

    cout << "test func parameter" << endl;
    func(_1);
    r_func(_1);
    p_func(&_1);

    return 0;
}

void func(Apple app) {
    cout << "func" << endl;
}

void r_func(Apple &app) {
    cout << "r_func" << endl;
}

void p_func(Apple *app) {
    cout << "p_func" << endl;
}
